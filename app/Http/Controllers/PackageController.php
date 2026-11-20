<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Service;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'studio_id' => 'sometimes|integer|exists:studios,id',
            'type' => 'sometimes|string|max:40',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = Package::with(['studio', 'services', 'images']);
        $user = $request->user();

        if ($user?->role === 'provider') {
            $query->whereHas('studio', fn ($q) =>
                $q->where('provider_id', $user->id)
            );
        } elseif ($user?->role !== 'admin') {
            $this->visibleToCustomers($query);
        }

        if (isset($filters['studio_id'])) {
            $query->where('studio_id', $filters['studio_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        $packages = $query->latest('id')
            ->paginate($filters['per_page'] ?? 12);

        if (!in_array($user?->role, ['admin', 'provider'], true)) {
            $packages->getCollection()->each(
                fn (Package $package) => $this->sanitizePublic($package)
            );
        }

        return view('customer.packages.index', compact('packages'));
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

    public function show(
        Request $request,
        Package $package
    ): View {
        $this->checkVisibility($request, $package);

        $package->load(['studio', 'services', 'images']);

        $user = $request->user();

        $isOwner = $user?->role === 'provider'
            && (int) $package->studio?->provider_id === (int) $user->id;

        if ($user?->role !== 'admin' && !$isOwner) {
            $this->sanitizePublic($package);
        }

        return view('customer.packages.show', compact('package'));
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
