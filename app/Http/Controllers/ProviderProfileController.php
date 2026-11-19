<?php

namespace App\Http\Controllers;

use App\Models\ProviderProfile;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProviderProfileController extends Controller
{
    private const IMAGE_FIELDS = ['logo', 'cover'];

    public function index()
    {
        $user = $this->activeUser();

        if ($user->role === 'admin') {
            $profiles = ProviderProfile::with('user:id,name')
                ->latest('id')
                ->paginate(15);

            return view('admin.provider_profiles.index', compact('profiles'));
        } elseif ($user->role === 'provider' || $user->role === 'customer') {
            $profile = $user->providerProfile;

            if ($profile) {
                return redirect()->route('provider-profiles.show', $profile);
            }

            if ($user->role === 'customer') {
                return redirect()->route('provider-profiles.create');
            }

            abort(404);
        }

        abort(403);
    }

    public function create()
    {
        $user = $this->activeUser();
        abort_unless($user->role === 'customer', 403);

        $profile = $user->providerProfile;

        if ($profile) {
            return redirect()->route('provider-profiles.show', $profile);
        }

        return view('provider.provider_profiles.create');
    }

    public function store(Request $request)
    {
        $user = $this->activeUser();
        abort_unless($user->role === 'customer', 403);

        $validated = $request->validate($this->profileRules('required'));

        $profile = $this->saveWithImages($validated, function (array $data) use ($user) {
            $owner = User::lockForUpdate()->findOrFail($user->id);

            abort_unless($owner->role === 'customer' && $owner->status === 'active', 403);

            $profile = ProviderProfile::withTrashed()
                ->firstOrNew(['user_id' => $owner->id]);

            abort_if(
                $profile->exists && !$profile->trashed(),
                409,
                'You already have a provider profile.'
            );

            $oldImages = $this->replacedImagePaths($profile, $data);
            $slug = Str::slug($data['company_name']);

            $profile->fill($data);
            $profile->slug = Str::limit($slug ?: 'provider', 150, '') . '-' . $owner->id;
            $profile->status = 'pending';
            $profile->admin_note = null;

            if ($profile->trashed()) {
                $profile->restore();
            } else {
                $profile->save();
            }

            return [$profile, $oldImages];
        });

        return redirect()->route('provider-profiles.show', $profile)
            ->with('success', 'Your request was submitted successfully!');
    }

    public function show(ProviderProfile $providerProfile)
    {
        $user = $this->activeUser();
        $this->ensureProfileAccess($user, $providerProfile);

        $profile = $providerProfile;

        if ($user->role === 'admin') {
            $profile->load('user:id,name');
        }

        return view('provider.provider_profiles.show', compact('profile'));
    }

    public function edit(ProviderProfile $providerProfile)
    {
        $user = $this->activeUser();
        $this->ensureProfileAccess($user, $providerProfile);

        $profile = $providerProfile;

        return view('provider.provider_profiles.edit', compact('profile'));
    }

    public function update(Request $request, ProviderProfile $providerProfile)
    {
        $user = $this->activeUser();
        $this->ensureProfileAccess($user, $providerProfile);

        $validated = $request->validate($this->profileRules('sometimes|required'));

        $profile = $this->saveWithImages($validated, function (array $data) use ($providerProfile) {
            $profile = ProviderProfile::lockForUpdate()
                ->findOrFail($providerProfile->id);

            $oldImages = $this->replacedImagePaths($profile, $data);

            $profile->fill($data);

            if ($profile->isDirty() && $profile->status === 'rejected') {
                $profile->status = 'pending';
                $profile->admin_note = null;
            }

            $profile->save();

            return [$profile, $oldImages];
        });

        return redirect()->route('provider-profiles.show', $profile)
            ->with('success', 'Provider profile updated successfully!');
    }

    public function review(Request $request, ProviderProfile $providerProfile)
    {
        $admin = $this->activeUser();
        abort_unless($admin->role === 'admin', 403);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($providerProfile, $validated) {
            $owner = User::lockForUpdate()->findOrFail($providerProfile->user_id);
            $profile = ProviderProfile::lockForUpdate()
                ->findOrFail($providerProfile->id);

            abort_unless($profile->status === 'pending', 409, 'This request is not pending.');

            if ($validated['status'] === 'approved') {
                abort_unless(
                    $owner->role === 'customer' && $owner->status === 'active',
                    409,
                    'Only an active customer can become a provider.'
                );

                $owner->update(['role' => 'provider']);
            }

            $profile->update([
                'status' => $validated['status'],
                'admin_note' => $validated['admin_note'] ?? null,
            ]);
        });

        return redirect()->route('provider-profiles.show', $providerProfile)
            ->with('success', 'Provider request reviewed successfully!');
    }

    public function destroy(ProviderProfile $providerProfile)
    {
        $user = $this->activeUser();
        $this->ensureProfileAccess($user, $providerProfile);

        DB::transaction(function () use ($providerProfile) {
            $owner = User::withTrashed()
                ->lockForUpdate()
                ->findOrFail($providerProfile->user_id);

            $profile = ProviderProfile::lockForUpdate()
                ->findOrFail($providerProfile->id);

            $profile->delete();

            if ($owner->role === 'provider') {
                $owner->update(['role' => 'customer']);
            }
        });

        $route = $user->role === 'admin' ? 'provider-profiles.index' : 'account.show';

        return redirect()->route($route)
            ->with('success', 'Provider profile deleted successfully!');
    }

    private function profileRules(string $companyNameRule): array
    {
        return [
            'company_name' => $companyNameRule . '|string|max:150',
            'tagline' => 'nullable|string|max:160',
            'bio' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'instagram' => 'nullable|string|max:120',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ];
    }

    private function saveWithImages(array $data, Closure $saveProfile): ProviderProfile
    {
        $disk = Storage::disk('public');
        $newImages = [];

        try {
            foreach (self::IMAGE_FIELDS as $field) {
                $image = $data[$field] ?? null;
                unset($data[$field]);

                if (!$image) {
                    continue;
                }

                $path = $image->store('provider-profiles/' . $field, 'public');

                if ($path === false) {
                    throw ValidationException::withMessages([
                        $field => 'Unable to upload the image. Try again.',
                    ]);
                }

                $newImages[] = $path;
                $data[$field] = $path;
            }

            [$profile, $oldImages] = DB::transaction(fn () => $saveProfile($data));
        } catch (Throwable $exception) {
            foreach ($newImages as $path) {
                $disk->delete($path);
            }

            throw $exception;
        }

        foreach ($oldImages as $path) {
            $disk->delete($path);
        }

        return $profile;
    }

    private function replacedImagePaths(ProviderProfile $profile, array $data): array
    {
        $paths = [];

        foreach (self::IMAGE_FIELDS as $field) {
            $oldPath = $profile->$field;

            if (
                isset($data[$field])
                && $oldPath
                && dirname($oldPath) === 'provider-profiles/' . $field
            ) {
                $paths[] = $oldPath;
            }
        }

        return $paths;
    }

    private function activeUser(): User
    {
        $user = Auth::user();

        if (!$user instanceof User || $user->status !== 'active') {
            abort(403);
        }

        return $user;
    }

    private function ensureProfileAccess(User $user, ProviderProfile $profile): void
    {
        abort_unless($user->role === 'admin' || (int) $profile->user_id === $user->id, 403);
    }
}