<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ScopesToProvider;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ScopesToProvider;

    public function edit(Request $request)
    {
        $profile = $request->user()->providerProfile;

        if (! $profile) {
            return redirect()->route('provider.dashboard')
                ->withErrors(['profile' => 'Your studio profile was not found. Please contact support.']);
        }

        return view('provider.panel.profile.edit', [
            'profile' => $profile,
            'user'    => $request->user(),
            'cities'  => StudioController::CITIES,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $profile = $user->providerProfile;

        abort_unless($profile, 404);

        if (! $profile->isApproved()) {
            return back()->withErrors(['profile' => 'Your profile can be edited once your studio account is approved.']);
        }

        $data = $request->validate([
            'company_name' => 'required|string|max:150',
            'city'         => 'required|string|in:' . implode(',', StudioController::CITIES),
            'tagline'      => 'nullable|string|max:160',
            'bio'          => 'nullable|string|max:2000',
            'instagram'    => 'nullable|string|max:120',
            'logo'         => 'nullable|image|mimes:jpeg,png,webp|max:4096',
            'cover'        => 'nullable|image|mimes:jpeg,png,webp|max:6144',
            'name'         => 'required|string|max:255',
            'phone'        => 'nullable|string|max:25',
        ]);

        $fields = collect($data)->only(['company_name', 'city', 'tagline', 'bio', 'instagram'])->all();

        foreach (['logo' => 'providers/logos', 'cover' => 'providers/covers'] as $field => $dir) {
            if ($request->hasFile($field)) {
                $this->deleteFile($profile->{$field});
                $fields[$field] = $request->file($field)->store($dir, 'public');
            }
        }

        $profile->update($fields);
        $user->update(['name' => $data['name'], 'phone' => $data['phone'] ?? null]);

        return redirect()->route('provider.profile.edit')->with('status', 'Profile saved.');
    }
}
