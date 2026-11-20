<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\PackageImage;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PackageImageController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'package_id' => 'sometimes|integer|exists:packages,id',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = PackageImage::with(
            'package:id,studio_id,name,is_active'
        );

        $user = $request->user();

        if ($user?->role === 'provider') {
            $query->whereHas('package.studio', fn (Builder $q) =>
                $q->where('provider_id', $user->id)
            );
        } elseif ($user?->role !== 'admin') {
            $this->visibleToCustomers($query);
        }

        if (isset($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }

        $images = $query->orderBy('sort_order')
            ->paginate($filters['per_page'] ?? 15);

        return view('customer.package-images.index', compact('images'));
    }

    public function create(Request $request): View
    {
        $user = $this->manager($request);

        $query = Package::query()->select([
            'id',
            'name',
            'studio_id',
        ]);

        if ($user->role === 'provider') {
            $query->whereHas('studio', fn (Builder $q) =>
                $q->where('provider_id', $user->id)
            );
        }

        $packages = $query->orderBy('name')->get();

        return view('package-images.create', compact('packages'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $this->manager($request);

        $data = $request->validate([
            'package_id' => 'required|integer|exists:packages,id',
            'image' => 'required|image|mimes:jpeg,png,webp|max:5120',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $package = Package::findOrFail($data['package_id']);

        $this->checkOwner($user, $package);

        $path = $request->file('image')->store('packages', 'public');

        try {
            PackageImage::create([
                'package_id' => $package->id,
                'image_path' => $path,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return redirect()
            ->route('packages.show', $package)
            ->with('success', 'Package image uploaded successfully.');
    }

    public function show(
        Request $request,
        PackageImage $packageImage
    ): View {
        $this->checkVisibility($request, $packageImage);

        $packageImage->load('package:id,studio_id,name,is_active');

        return view('customer.package-images.show', compact('packageImage'));
    }

    public function edit(
        Request $request,
        PackageImage $packageImage
    ): View {
        $user = $this->manager($request);

        $this->checkOwner($user, $packageImage->package);

        return view('package-images.edit', compact('packageImage'));
    }

    public function update(
        Request $request,
        PackageImage $packageImage
    ): RedirectResponse {
        $user = $this->manager($request);

        $this->checkOwner($user, $packageImage->package);

        $data = $request->validate([
            'package_id' => 'prohibited',
            'image' => 'sometimes|image|mimes:jpeg,png,webp|max:5120',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $oldPath = $packageImage->image_path;
        $newPath = null;
        $changes = [];

        if (array_key_exists('sort_order', $data)) {
            $changes['sort_order'] = $data['sort_order'];
        }

        if ($request->hasFile('image')) {
            $newPath = $request->file('image')->store('packages', 'public');
            $changes['image_path'] = $newPath;
        }

        try {
            $packageImage->update($changes);
        } catch (\Throwable $e) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }

            throw $e;
        }

        if ($newPath && $oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()
            ->route('packages.show', $packageImage->package)
            ->with('success', 'Package image updated successfully.');
    }

    public function destroy(
        Request $request,
        PackageImage $packageImage
    ): RedirectResponse {
        $user = $this->manager($request);

        $this->checkOwner($user, $packageImage->package);

        $package = $packageImage->package;
        $packageImage->delete();

        return redirect()
            ->route('packages.show', $package)
            ->with('success', 'Package image deleted successfully.');
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

    private function checkOwner(User $user, Package $package): void
    {
        if ($user->role === 'provider') {
            abort_unless(
                (int) $package->studio?->provider_id === (int) $user->id,
                403
            );
        }
    }

    private function visibleToCustomers(Builder $query): void
    {
        $query->whereHas('package', fn (Builder $package) =>
            $package->where('is_active', true)
                ->whereHas('studio', fn (Builder $studio) =>
                    $studio->where('status', 'approved')
                        ->where('is_active', true)
                        ->whereHas('provider', fn (Builder $provider) =>
                            $provider->where('status', 'active')
                                ->whereHas('providerProfile', fn (Builder $profile) =>
                                    $profile->where('status', 'approved')
                                )
                        )
                )
        );
    }

    private function checkVisibility(
        Request $request,
        PackageImage $packageImage
    ): void {
        $user = $request->user();

        if ($user?->role === 'admin') {
            return;
        }

        if (
            $user?->role === 'provider' &&
            (int) $packageImage->package?->studio?->provider_id
                === (int) $user->id
        ) {
            return;
        }

        $query = PackageImage::whereKey($packageImage->id);

        $this->visibleToCustomers($query);

        abort_unless($query->exists(), 404);
    }
}
