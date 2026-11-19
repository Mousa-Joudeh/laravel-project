<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => 'sometimes|string|max:150',
            'specialty' => 'sometimes|string|max:100',
            'kind' => 'sometimes|in:core,addon,both',
            'studio_id' => 'sometimes|integer|exists:studios,id',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $user = $request->user();
        $query = Service::query();

        if ($user?->role === 'provider') {
            $query->whereHas('studio', fn ($q) =>
                $q->where('provider_id', $user->id)
            );
        } elseif ($user?->role !== 'admin') {
            $this->visibleToCustomers($query);
        }

        if (!empty($filters['q'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['q'] . '%')
                    ->orWhere('description', 'like', '%' . $filters['q'] . '%');
            });
        }

        if (!empty($filters['specialty'])) {
            $query->where('specialty', $filters['specialty']);
        }

        if (!empty($filters['kind'])) {
            $query->where('kind', $filters['kind']);
        }

        if (isset($filters['studio_id'])) {
            $query->where('studio_id', $filters['studio_id']);
        }

        $items = $query->latest('id')
            ->paginate($filters['per_page'] ?? 12);

        if ($user?->role !== 'admin' && $user?->role !== 'provider') {
            $items->getCollection()->each(
                fn ($item) => $item->makeHidden('admin_note')
            );
        }

        return response()->json($items);
    }

    public function create(Request $request): JsonResponse
    {
        $user = $this->manager($request);

        $studios = Studio::query()->select(['id', 'name']);

        if ($user->role === 'provider') {
            $studios->where('provider_id', $user->id);
        }

        return response()->json([
            'studios' => $studios->orderBy('name')->get(),
            'kinds' => ['core', 'addon', 'both'],
            'delivery_modes' => ['at_studio', 'at_client', 'online'],
            'pricing_types' => ['hourly', 'fixed'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $this->manager($request);
        $data = $this->validatedFields($request, true);

        $studio = Studio::findOrFail($data['studio_id']);

        $this->checkStudioOwner($user, $studio);
        $this->validateHours($data);

        $data['slug'] = $this->makeSlug($data['title']);
        $data['status'] = 'pending';

        if ($user->role === 'admin') {
            $data = array_merge($data, $request->validate([
                'status' => 'sometimes|in:draft,pending,approved,rejected',
                'admin_note' => 'sometimes|nullable|string|max:255',
            ]));
        } elseif ($request->exists('status') || $request->exists('admin_note')) {
            abort(403);
        }

        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('services', 'public');
            $data['image'] = $newImage;
        }

        try {
            $service = Service::create($data);
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Service created',
            'service' => $service,
        ], 201);
    }

    public function show(Request $request, Service $service): JsonResponse
    {
        $user = $request->user();

        if ($user?->role === 'admin') {
            return response()->json($service);
        }

        if (
            $user?->role === 'provider' &&
            (int) $service->studio?->provider_id === (int) $user->id
        ) {
            return response()->json($service);
        }

        $query = Service::whereKey($service->id);

        $this->visibleToCustomers($query);

        abort_unless($query->exists(), 404);

        return response()->json(
            $service->makeHidden('admin_note')
        );
    }

    public function edit(Request $request, Service $service): JsonResponse
    {
        $user = $this->manager($request);

        $this->checkServiceOwner($user, $service);

        return response()->json([
            'service' => $service,
            'studio' => $service->studio,
        ]);
    }

    public function update(
        Request $request,
        Service $service
    ): JsonResponse {
        $user = $this->manager($request);

        $this->checkServiceOwner($user, $service);

        $data = $this->validatedFields($request, false);

        $this->validateHours($data, $service);

        if (
            isset($data['title']) &&
            $data['title'] !== $service->title
        ) {
            $data['slug'] = $this->makeSlug($data['title']);
        }

        if ($user->role === 'admin') {
            $data = array_merge($data, $request->validate([
                'status' => 'sometimes|in:draft,pending,approved,rejected',
                'admin_note' => 'sometimes|nullable|string|max:255',
            ]));
        } else {
            if ($request->exists('status') || $request->exists('admin_note')) {
                abort(403);
            }

            if (
                count(array_diff(array_keys($data), ['is_active'])) > 0 ||
                $request->hasFile('image')
            ) {
                $data['status'] = 'pending';
            }
        }

        $oldImage = $service->image;
        $newImage = null;

        if ($request->hasFile('image')) {
            $newImage = $request->file('image')->store('services', 'public');
            $data['image'] = $newImage;
        }

        try {
            $service->update($data);
        } catch (\Throwable $e) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            throw $e;
        }

        if ($newImage && $oldImage) {
            Storage::disk('public')->delete($oldImage);
        }

        return response()->json([
            'message' => 'Service updated',
            'service' => $service->refresh(),
        ]);
    }

    public function destroy(
        Request $request,
        Service $service
    ): JsonResponse {
        $user = $this->manager($request);

        $this->checkServiceOwner($user, $service);

        $service->delete();

        return response()->json([
            'message' => 'Service deleted',
        ]);
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

    private function checkServiceOwner(User $user, Service $service): void
    {
        $this->checkStudioOwner($user, $service->studio);
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
            'title' => [$presence, 'string', 'max:150'],
            'specialty' => [$presence, 'string', 'max:100'],
            'description' => 'sometimes|nullable|string',
            'kind' => 'sometimes|in:core,addon,both',
            'delivery_mode' => 'sometimes|in:at_studio,at_client,online',
            'pricing_type' => 'sometimes|in:hourly,fixed',
            'price' => [
                $presence,
                'numeric',
                'min:0',
                'max:9999999.999'
            ],
            'min_hours' => 'sometimes|integer|min:1',
            'max_hours' => 'sometimes|integer|min:1',
            'price_includes_studio' => 'sometimes|boolean',
            'resource_group' => 'sometimes|nullable|string|max:80',
            'is_active' => 'sometimes|boolean',
            'image' => 'sometimes|image|mimes:jpeg,png,webp|max:4096',
        ]);
    }

    private function validateHours(
        array $data,
        ?Service $service = null
    ): void {
        $min = $data['min_hours'] ?? $service?->min_hours ?? 1;
        $max = $data['max_hours'] ?? $service?->max_hours ?? 4;

        if ($min > $max) {
            throw ValidationException::withMessages([
                'max_hours' => 'Maximum hours must be greater than or equal to minimum hours.',
            ]);
        }
    }

    private function makeSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'service';

        do {
            $slug = substr($base, 0, 170)
                . '-' . Str::lower(Str::random(7));
        } while (DB::table('services')->where('slug', $slug)->exists());

        return $slug;
    }

    private function visibleToCustomers(Builder $query): void
    {
        $query->where('status', 'approved')
            ->where('is_active', true)
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
}
