<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ScopesToProvider;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    use ScopesToProvider;

    public function index()
    {
        $services = Service::whereHas('studio', fn ($q) => $q->where('provider_id', auth()->id()))
            ->with('studio')
            ->latest()
            ->get();

        $ratings = $this->ratingMap('service_id', $services->pluck('id'));

        return view('provider.panel.services.index', compact('services', 'ratings'));
    }

    public function create()
    {
        return view('provider.panel.services.create', [
            'service' => new Service([
                'kind' => 'core', 'delivery_mode' => 'at_studio', 'pricing_type' => 'hourly',
                'min_hours' => 1, 'max_hours' => 4, 'specialty' => 'photographer',
            ]),
            'studios' => $this->myStudios()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, true);

        $studio = $this->ownStudio(\App\Models\Studio::findOrFail($data['studio_id']));

        $data['slug'] = $this->makeSlug($data['title']);
        $data['status'] = 'pending';

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        $service = $studio->services()->create($data);

        return redirect()->route('provider.services.show', $service)
            ->with('status', 'Your service was sent to the admin for approval.');
    }

    public function show(Service $service)
    {
        $this->ownService($service)->load('studio');

        $bookingsCount = $service->bookingItems()->distinct('booking_id')->count('booking_id');

        $reviews = \App\Models\Review::whereHas('booking.items', fn ($q) => $q->where('service_id', $service->id))
            ->with('booking.customer')
            ->latest()
            ->limit(5)
            ->get();

        return view('provider.panel.services.show', compact('service', 'bookingsCount', 'reviews'));
    }

    public function edit(Service $service)
    {
        $this->ownService($service);

        return view('provider.panel.services.edit', [
            'service' => $service,
            'studios' => $this->myStudios()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Service $service)
    {
        $this->ownService($service);

        $data = $this->validated($request, false);
        $data['is_active'] = $request->boolean('is_active');

        if ($data['title'] !== $service->title) {
            $data['slug'] = $this->makeSlug($data['title']);
        }

        $oldImage = $service->image;

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('services', 'public');
        }

        $service->fill($data);

        // Changing anything except the on/off switch sends it back to review.
        if ($service->isDirty(array_diff(array_keys($data), ['is_active']))) {
            $service->status = 'pending';
        }

        $service->save();

        if ($request->hasFile('image')) {
            $this->deleteFile($oldImage);
        }

        return redirect()->route('provider.services.show', $service)->with('status', 'Service updated.');
    }

    public function destroy(Service $service)
    {
        $this->ownService($service);

        $service->delete();

        return redirect()->route('provider.services.index')->with('status', 'Service deleted.');
    }

    private function validated(Request $request, bool $creating): array
    {
        $data = $request->validate([
            'studio_id'             => $creating ? 'required|integer|exists:studios,id' : 'prohibited',
            'title'                 => 'required|string|max:150',
            'specialty'             => 'required|string|max:100',
            'description'           => 'required|string|min:30',
            'kind'                  => 'required|in:core,addon,both',
            'delivery_mode'         => 'required|in:at_studio,at_client,online',
            'pricing_type'          => 'required|in:hourly,fixed',
            'price'                 => 'required|numeric|min:1|max:9999999.999',
            'min_hours'             => 'required|integer|min:1|max:24',
            'max_hours'             => 'required|integer|min:1|max:24',
            'price_includes_studio' => 'nullable|boolean',
            'resource_group'        => 'nullable|string|max:80',
            'image'                 => 'nullable|image|mimes:jpeg,png,webp|max:4096',
        ]);

        if ($data['min_hours'] > $data['max_hours']) {
            throw ValidationException::withMessages([
                'max_hours' => 'Maximum hours must be greater than or equal to minimum hours.',
            ]);
        }

        $data['price_includes_studio'] = $request->boolean('price_includes_studio');
        unset($data['image']);

        return $data;
    }

    private function makeSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'service';

        do {
            $slug = substr($base, 0, 170) . '-' . Str::lower(Str::random(7));
        } while (DB::table('services')->where('slug', $slug)->exists());

        return $slug;
    }
}
