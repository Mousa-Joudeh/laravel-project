<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ScopesToProvider;
use App\Models\Category;
use App\Models\Studio;
use App\Models\StudioType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StudioController extends Controller
{
    use ScopesToProvider;

    public const CITIES = ['Amman', 'Irbid', 'Zarqa', 'Aqaba', 'Salt', 'Madaba', 'Jerash', 'Karak', 'Mafraq', 'Ajloun'];

    public const FACILITIES = [
        'Wi-Fi', 'Parking', 'Air conditioning', 'Changing room', 'Makeup area', 'Kitchenette',
        'Restrooms', 'Wheelchair access', 'Blackout curtains', 'Sound-proofing', 'Green screen', 'Props',
        'Coffee & water',
    ];

    private const MAX_PHOTOS = 10;

    public function index()
    {
        $studios = $this->myStudios()
            ->with(['type', 'category', 'images'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->latest()
            ->get();

        return view('provider.panel.studios.index', compact('studios'));
    }

    public function create()
    {
        return view('provider.panel.studios.create', ['studio' => new Studio(['capacity' => 1])] + $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request, null);

        $studio = $request->user()->studios()->create($data['fields'] + [
            'slug'      => Str::slug($data['fields']['name']) . '-' . Str::lower(Str::random(6)),
            'status'    => 'pending',
            'is_active' => true,
        ]);

        $this->addImages($studio, $request->file('images', []));

        return redirect()->route('provider.studios.show', $studio)
            ->with('status', 'Your space was sent to the admin for approval.');
    }

    public function show(Studio $studio)
    {
        $this->ownStudio($studio)->load(['category', 'type', 'images', 'services', 'packages']);

        $bookings = $studio->bookings()->with('customer')->latest('starts_at')->limit(5)->get();

        return view('provider.panel.studios.show', compact('studio', 'bookings'));
    }

    public function edit(Studio $studio)
    {
        $this->ownStudio($studio)->load('images');

        return view('provider.panel.studios.edit', compact('studio') + $this->formData());
    }

    public function update(Request $request, Studio $studio)
    {
        $this->ownStudio($studio);

        $data = $this->validated($request, $studio);

        $fields = $data['fields'] + ['is_active' => $request->boolean('is_active')];

        // A rejected space goes back to review after the owner fixes it.
        if ($studio->status === 'rejected') {
            $fields['status'] = 'pending';
        }

        $studio->update($fields);

        $this->addImages($studio, $request->file('images', []));

        return redirect()->route('provider.studios.show', $studio)->with('status', 'Space updated.');
    }

    public function destroy(Studio $studio)
    {
        $this->ownStudio($studio);

        if ($studio->bookings()->exists()) {
            return back()->withErrors(['studio' => 'This space has bookings, so it cannot be deleted. Hide it instead.']);
        }

        $studio->delete();

        return redirect()->route('provider.studios.index')->with('status', 'Space deleted.');
    }

    private function formData(): array
    {
        return [
            'types'      => StudioType::orderBy('sort_order')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'cities'     => self::CITIES,
            'facilities' => self::FACILITIES,
        ];
    }

    private function validated(Request $request, ?Studio $studio): array
    {
        $existing = $studio ? $studio->images()->count() : 0;
        $room = max(0, self::MAX_PHOTOS - $existing);

        $v = $request->validate([
            'name'                => 'required|string|max:150',
            'category_id'         => 'required|exists:categories,id',
            'studio_type_id'      => 'required|exists:studio_types,id',
            'description'         => 'required|string|min:40',
            'city'                => 'required|string|in:' . implode(',', self::CITIES),
            'address'             => 'required|string|max:255',
            'capacity'            => 'required|integer|min:1',
            'area_sqm'            => 'nullable|numeric|min:1',
            'hourly_price'        => 'required|numeric|min:1|max:9999999',
            'facilities'          => 'nullable|array',
            'facilities.*'        => 'string|max:60',
            'equipment'           => 'nullable|array',
            'equipment.*.item'    => 'nullable|string|max:100',
            'equipment.*.qty'     => 'nullable|integer|min:1|max:999',
            'equipment.*.detail'  => 'nullable|string|max:150',
            'images'              => ($studio ? 'nullable' : 'required') . '|array|max:' . $room,
            'images.*'            => 'image|mimes:jpeg,png,webp|max:4096',
        ], [
            'images.max' => "You can have up to " . self::MAX_PHOTOS . " photos per space.",
        ]);

        $equipment = collect($v['equipment'] ?? [])
            ->filter(fn ($row) => filled($row['item'] ?? null))
            ->map(fn ($row) => [
                'item'   => trim($row['item']),
                'qty'    => (int) ($row['qty'] ?? 1),
                'detail' => trim($row['detail'] ?? ''),
            ])
            ->values()
            ->all();

        $fields = collect($v)->only([
            'name', 'category_id', 'studio_type_id', 'description', 'city', 'address',
            'capacity', 'area_sqm', 'hourly_price',
        ])->all();

        $fields['facilities'] = array_values($v['facilities'] ?? []);
        $fields['included_equipment'] = $equipment;

        return ['fields' => $fields];
    }

    private function addImages(Studio $studio, array $files): void
    {
        if (! $files) {
            return;
        }

        $hasCover = $studio->images()->where('is_cover', true)->exists();
        $order = (int) $studio->images()->max('sort_order');

        foreach ($this->storeFiles($files, 'studios/' . $studio->id) as $path) {
            $studio->images()->create([
                'image_path' => $path,
                'is_cover'   => ! $hasCover,
                'sort_order' => ++$order,
            ]);

            $hasCover = true;
        }
    }
}
