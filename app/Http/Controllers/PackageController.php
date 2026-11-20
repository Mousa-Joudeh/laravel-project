<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Review;
use App\Models\Service;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => 'sometimes|nullable|string|max:150',
            'types' => 'sometimes|array|max:20',
            'types.*' => ['string', Rule::in(array_keys(Package::TYPES))],
            'type' => 'sometimes|string|max:40',
            'studio_id' => 'sometimes|integer|exists:studios,id',
            'sort' => 'sometimes|nullable|in:price_asc,price_desc',
            'max_price' => 'sometimes|nullable|numeric|min:0|max:9999999.999',
        ]);

        $query = Package::query()->with([
            'studio.images' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order'),
            'services',
            'images' => fn ($q) => $q->orderBy('sort_order'),
        ]);

        $user = $request->user();

        if ($user?->role === 'provider') {
            $query->whereHas('studio', fn (Builder $q) =>
                $q->where('provider_id', $user->id)
            );
        } elseif ($user?->role !== 'admin') {
            $this->visibleToCustomers($query);
        }

        $types = Package::TYPES;
        $typeCounts = array_fill_keys(array_keys($types), 0);

        foreach ((clone $query)->select('type')
            ->selectRaw('COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type') as $type => $count) {
            if (array_key_exists($type, $typeCounts)) {
                $typeCounts[$type] = (int) $count;
            }
        }

        if (!empty($filters['q'])) {
            $query->where('name', 'like', '%' . trim($filters['q']) . '%');
        }

        if (!empty($filters['types'])) {
            $query->whereIn('type', $filters['types']);
        } elseif (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['studio_id'])) {
            $query->where('studio_id', $filters['studio_id']);
        }

        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        if (($filters['sort'] ?? null) === 'price_asc') {
            $query->orderBy('price')->orderByDesc('id');
        } elseif (($filters['sort'] ?? null) === 'price_desc') {
            $query->orderByDesc('price')->orderByDesc('id');
        } else {
            $query->latest('id');
        }

        $packages = $query->paginate(9)->withQueryString();
        $this->attachPackagePresentation($packages->getCollection());

        if (!in_array($user?->role, ['admin', 'provider'], true)) {
            $packages->getCollection()->each(
                fn (Package $package) => $this->sanitizePublic($package)
            );
        }

        return view('customer.packages.index', compact('packages', 'types', 'typeCounts'));
    }

    public function create(Request $request): View
    {
        $user = $this->manager($request);

        $studios = Studio::query()->select(['id', 'name']);

        if ($user->role === 'provider') {
            $studios->where('provider_id', $user->id);
        }

        $studios = $studios->orderBy('name')->get();

        return view('packages.create', compact('studios'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->manager($request);
        $data = $this->validatedFields($request, true);

        $studio = Studio::findOrFail($data['studio_id']);

        $this->checkStudioOwner($user, $studio);
        $this->validateRanges($data);

        $data['slug'] = $this->makeSlug($data['name']);

        $package = Package::create($data);

        return redirect()
            ->route('packages.show', $package)
            ->with('success', 'Package created successfully.');
    }

    public function show(Request $request, Package $package): View
    {
        $this->checkVisibility($request, $package);

        $user = $request->user();
        $isOwner = $user?->role === 'provider'
            && (int) $package->studio?->provider_id === (int) $user->id;
        $canManage = $user?->role === 'admin' || $isOwner;

        $package->load([
            'studio.images' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order'),
            'studio.provider.providerProfile',
            'services',
            'images' => fn ($q) => $q->orderBy('sort_order'),
            'bookingItems' => function ($q) use ($canManage, $user) {
                if (!$canManage) {
                    $q->whereHas('booking', fn ($booking) =>
                        $booking->where('customer_id', $user?->id ?? 0)
                    );
                }
            },
        ]);

        $this->attachPackagePresentation(collect([$package]));

        $reviews = Review::query()
            ->whereHas('booking.items', fn ($q) =>
                $q->where('package_id', $package->id)
            )
            ->with(['booking:id,customer_id', 'booking.customer:id,name,avatar'])
            ->latest()
            ->paginate(6, ['*'], 'reviews_page')
            ->withQueryString();

        $addons = Service::query()
            ->where('studio_id', $package->studio_id)
            ->whereIn('kind', ['addon', 'both'])
            ->where('status', 'approved')
            ->where('is_active', true)
            ->whereNotIn('id', $package->services->pluck('id'))
            ->orderBy('title')
            ->get();

        $addons->each(fn (Service $service) =>
            $service->setAttribute('image_url', $service->imageUrl())
        );

        $moreQuery = Package::query()
            ->with([
                'studio.images' => fn ($q) => $q->orderByDesc('is_cover')->orderBy('sort_order'),
                'images' => fn ($q) => $q->orderBy('sort_order'),
                'services',
            ])
            ->where('type', $package->type)
            ->whereKeyNot($package->id);

        $this->visibleToCustomers($moreQuery);

        $morePackages = $moreQuery->latest('id')->limit(3)->get();
        $this->attachPackagePresentation($morePackages);
        $morePackages->each(fn (Package $item) => $this->sanitizePublic($item));

        $photos = $package->images
            ->concat($package->studio?->images ?? collect())
            ->map(fn ($image) => $image->url())
            ->values()
            ->all();

        if (empty($photos)) {
            $photos = [asset('images/placeholder.svg')];
        }

        if (!$canManage) {
            $this->sanitizePublic($package);
        }

        return view('customer.packages.show', compact(
            'package', 'photos', 'reviews', 'addons', 'morePackages'
        ));
    }

    public function edit(
        Request $request,
        Package $package
    ): View {
        $user = $this->manager($request);

        $this->checkStudioOwner($user, $package->studio);

        $package->load(['studio', 'services', 'images']);

        return view('packages.edit', compact('package'));
    }

    public function update(
        Request $request,
        Package $package
    ): RedirectResponse {
        $user = $this->manager($request);

        $this->checkStudioOwner($user, $package->studio);

        $data = $this->validatedFields($request, false);

        $this->validateRanges($data, $package);

        if (
            isset($data['name']) &&
            $data['name'] !== $package->name
        ) {
            $data['slug'] = $this->makeSlug($data['name']);
        }

        $package->update($data);

        return redirect()
            ->route('packages.show', $package)
            ->with('success', 'Package updated successfully.');
    }

    public function destroy(
        Request $request,
        Package $package
    ): RedirectResponse {
        $user = $this->manager($request);

        $this->checkStudioOwner($user, $package->studio);

        $package->delete();

        return redirect()
            ->route('packages.index')
            ->with('success', 'Package deleted successfully.');
    }

    public function syncServices(
        Request $request,
        Package $package
    ): RedirectResponse {
        $user = $this->manager($request);

        $this->checkStudioOwner($user, $package->studio);

        $data = $request->validate([
            'service_ids' => 'present|array|max:100',
            'service_ids.*' => 'integer|distinct|exists:services,id',
        ]);

        $serviceIds = $data['service_ids'];

        $validCount = Service::whereIn('id', $serviceIds)
            ->where('studio_id', $package->studio_id)
            ->count();

        if ($validCount !== count($serviceIds)) {
            throw ValidationException::withMessages([
                'service_ids' => 'All services must belong to the same studio.',
            ]);
        }

        DB::transaction(function () use ($package, $serviceIds) {
            $package->services()->sync($serviceIds);
        });

        return redirect()
            ->route('packages.show', $package)
            ->with('success', 'Package services updated successfully.');
    }

    private function attachPackagePresentation(Collection $packages): void
    {
        if ($packages->isEmpty()) {
            return;
        }

        $stats = DB::table('booking_items as bi')
            ->join('bookings as b', 'b.id', '=', 'bi.booking_id')
            ->join('reviews as r', 'r.booking_id', '=', 'b.id')
            ->whereIn('bi.package_id', $packages->pluck('id'))
            ->whereNull('bi.deleted_at')
            ->whereNull('b.deleted_at')
            ->whereNull('r.deleted_at')
            ->selectRaw('bi.package_id, AVG(r.rating) as avg_rating, COUNT(DISTINCT r.booking_id) as reviews_total')
            ->groupBy('bi.package_id')
            ->get()
            ->keyBy('package_id');

        foreach ($packages as $package) {
            $row = $stats->get($package->id);
            $package->setAttribute('rating', $row ? round((float) $row->avg_rating, 1) : 0.0);
            $package->setAttribute('reviews_total', (int) ($row->reviews_total ?? 0));
            $package->setAttribute(
                'cover_url',
                $package->images->first()?->url()
                    ?? $package->studio?->images?->first()?->url()
                    ?? asset('images/placeholder.svg')
            );
        }
    }

    private function manager(Request $request): User
    {
        $user = $request->user();

        abort_unless(
            $user &&
            $user->status === 'active' &&
            in_array($user->role, ['admin', 'provider'], true),
            403
        );

        return $user;
    }

    private function checkStudioOwner(User $user, Studio $studio): void
    {
        if ($user->role !== 'admin') {
            abort_unless(
                (int) $studio->provider_id === (int) $user->id,
                403
            );
        }
    }

    private function validatedFields(
        Request $request,
        bool $creating
    ): array {
        $presence = $creating ? 'required' : 'sometimes';

        return $request->validate([
            'studio_id' => $creating
                ? 'required|integer|exists:studios,id'
                : 'prohibited',
            'name' => [$presence, 'string', 'max:150'],
            'type' => 'sometimes|string|max:40',
            'description' => 'sometimes|nullable|string',
            'pricing_type' => 'sometimes|in:hourly,fixed',
            'price' => [
                $presence,
                'numeric',
                'min:0',
                'max:9999999.999'
            ],
            'min_hours' => 'sometimes|integer|min:1',
            'max_hours' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'start_date' => 'sometimes|nullable|date',
            'end_date' => 'sometimes|nullable|date',
        ]);
    }

    private function validateRanges(
        array $data,
        ?Package $package = null
    ): void {
        $min = $data['min_hours'] ?? $package?->min_hours ?? 1;
        $max = $data['max_hours'] ?? $package?->max_hours ?? 4;

        if ($max < $min) {
            throw ValidationException::withMessages([
                'max_hours' => 'Maximum hours must be greater than or equal to minimum hours.',
            ]);
        }

        $start = array_key_exists('start_date', $data)
            ? $data['start_date']
            : $package?->start_date;

        $end = array_key_exists('end_date', $data)
            ? $data['end_date']
            : $package?->end_date;

        if (
            $start !== null &&
            $end !== null &&
            Carbon::parse($end)->lt(Carbon::parse($start))
        ) {
            throw ValidationException::withMessages([
                'end_date' => 'End date must be on or after start date.',
            ]);
        }
    }

    private function makeSlug(string $name): string
    {
        return (Str::slug($name) ?: 'package')
            . '-' . Str::lower(Str::random(8));
    }

    private function visibleToCustomers(Builder $query): void
    {
        $query->where('is_active', true)
            ->whereHas('studio', fn ($studio) =>
                $studio->where('status', 'approved')
                    ->where('is_active', true)
                    ->whereHas('provider', fn ($provider) =>
                        $provider->where('status', 'active')
                            ->whereHas('providerProfile', fn ($profile) =>
                                $profile->where('status', 'approved')
                            )
                    )
            );
    }

    private function checkVisibility(
        Request $request,
        Package $package
    ): void {
        $user = $request->user();

        if ($user?->role === 'admin') {
            return;
        }

        if (
            $user?->role === 'provider' &&
            (int) $package->studio?->provider_id === (int) $user->id
        ) {
            return;
        }

        $query = Package::whereKey($package->id);

        $this->visibleToCustomers($query);

        abort_unless($query->exists(), 404);
    }

    private function sanitizePublic(Package $package): void
    {
        if ($package->studio) {
            $package->studio->makeHidden('admin_note');

            if ($package->studio->relationLoaded('provider')) {
                $provider = $package->studio->provider;

                if ($provider && $provider->relationLoaded('providerProfile')) {
                    $provider->providerProfile?->makeHidden('admin_note');
                }
            }
        }

        $services = $package->services
            ->filter(fn (Service $service) =>
                $service->status === 'approved' &&
                $service->is_active
            )
            ->values();

        $services->each(
            fn (Service $service) => $service->makeHidden('admin_note')
        );

        $package->setRelation('services', $services);
    }
}
