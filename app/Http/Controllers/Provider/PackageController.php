<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ScopesToProvider;
use App\Models\Package;
use App\Models\Review;
use App\Models\Service;
use App\Models\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PackageController extends Controller
{
    use ScopesToProvider;

    private const MAX_PHOTOS = 8;

    public function index()
    {
        $packages = Package::whereHas('studio', fn ($q) => $q->where('provider_id', auth()->id()))
            ->with(['studio', 'services', 'images'])
            ->latest()
            ->get();

        $ratings = $this->ratingMap('package_id', $packages->pluck('id'));

        return view('provider.panel.packages.index', compact('packages', 'ratings'));
    }

    public function create()
    {
        return view('provider.panel.packages.create', [
            'package' => new Package(['type' => 'portrait', 'pricing_type' => 'fixed', 'min_hours' => 1, 'max_hours' => 4]),
        ] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);
        $studio = $this->ownStudio(Studio::findOrFail($data['studio_id']));

        $this->checkServices($studio, $data['services']);

        $package = DB::transaction(function () use ($studio, $data) {
            $package = $studio->packages()->create(
                collect($data)->except('services')->all() + [
                    'slug'         => $this->makeSlug($data['name']),
                    'pricing_type' => 'fixed',
                    'is_active'    => true,
                ]
            );

            $package->services()->sync($data['services']);

            return $package;
        });

        $this->addImages($package, $request->file('images', []));

        return redirect()->route('provider.packages.show', $package)->with('status', 'Package created.');
    }

    public function show(Package $package)
    {
        $this->ownPackage($package)->load(['studio', 'services', 'images']);

        $bookingsCount = $package->bookingItems()->distinct('booking_id')->count('booking_id');

        $reviews = Review::whereHas('booking.items', fn ($q) => $q->where('package_id', $package->id))
            ->with('booking.customer')
            ->latest()
            ->limit(5)
            ->get();

        return view('provider.panel.packages.show', compact('package', 'bookingsCount', 'reviews'));
    }

    public function edit(Package $package)
    {
        $this->ownPackage($package)->load(['services', 'images']);

        return view('provider.panel.packages.edit', compact('package') + $this->formData());
    }

    public function update(Request $request, Package $package)
    {
        $this->ownPackage($package);

        // The space of an existing package cannot change.
        $request->merge(['studio_id' => $package->studio_id]);

        $data = $this->validated($request, $package);

        $this->checkServices($package->studio, $data['services']);

        $fields = collect($data)->except(['services', 'studio_id'])->all() + [
            'is_active' => $request->boolean('is_active'),
        ];

        if ($fields['name'] !== $package->name) {
            $fields['slug'] = $this->makeSlug($fields['name']);
        }

        DB::transaction(function () use ($package, $fields, $data) {
            $package->update($fields);
            $package->services()->sync($data['services']);
        });

        $this->addImages($package, $request->file('images', []));

        return redirect()->route('provider.packages.show', $package)->with('status', 'Package updated.');
    }

    public function destroy(Package $package)
    {
        $this->ownPackage($package);

        $package->delete();

        return redirect()->route('provider.packages.index')->with('status', 'Package deleted.');
    }

    private function formData(): array
    {
        return [
            'types'    => Package::TYPES,
            'studios'  => $this->myStudios()->orderBy('name')->get(['id', 'name']),
            'services' => Service::whereHas('studio', fn ($q) => $q->where('provider_id', auth()->id()))
                ->orderBy('title')->get(),
        ];
    }

    private function validated(Request $request, ?Package $package): array
    {
        $room = max(0, self::MAX_PHOTOS - ($package ? $package->images()->count() : 0));

        $data = $request->validate([
            'studio_id'   => 'required|integer|exists:studios,id',
            'name'        => 'required|string|max:150',
            'type'        => 'required|string|max:40',
            'description' => 'required|string|min:40',
            'price'       => 'required|numeric|min:1|max:9999999.999',
            'min_hours'   => 'required|integer|min:1|max:24',
            'max_hours'   => 'required|integer|min:1|max:24',
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'services'    => 'nullable|array|max:50',
            'services.*'  => 'integer|distinct|exists:services,id',
            'images'      => ($package ? 'nullable' : 'required') . '|array|max:' . $room,
            'images.*'    => 'image|mimes:jpeg,png,webp|max:4096',
        ], [
            'images.max' => 'You can have up to ' . self::MAX_PHOTOS . ' photos per package.',
        ]);

        if ($data['min_hours'] > $data['max_hours']) {
            throw ValidationException::withMessages([
                'max_hours' => 'Maximum hours must be greater than or equal to minimum hours.',
            ]);
        }

        $data['services'] = $data['services'] ?? [];
        unset($data['images']);

        return $data;
    }

    /** Every included service must belong to the package's own space. */
    private function checkServices(Studio $studio, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $valid = Service::whereIn('id', $ids)->where('studio_id', $studio->id)->count();

        if ($valid !== count($ids)) {
            throw ValidationException::withMessages([
                'services' => 'All services must belong to the space you picked.',
            ]);
        }
    }

    private function addImages(Package $package, array $files): void
    {
        $order = (int) $package->images()->max('sort_order');

        foreach ($this->storeFiles($files, 'packages/' . $package->id) as $path) {
            $package->images()->create(['image_path' => $path, 'sort_order' => ++$order]);
        }
    }

    private function makeSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'package';

        do {
            $slug = substr($base, 0, 170) . '-' . Str::lower(Str::random(7));
        } while (DB::table('packages')->where('slug', $slug)->exists());

        return $slug;
    }
}
