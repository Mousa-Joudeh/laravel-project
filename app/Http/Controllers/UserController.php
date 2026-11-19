<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $role = $this->activeRole();

        if ($role === 'admin') {
            $users = User::with('providerProfile')
                ->latest('id')
                ->paginate(15);

            return view('admin.users.index', compact('users'));
        } elseif ($role === 'provider') {
            $users = $this->providerCustomers()
                ->latest('id')
                ->paginate(15, ['id', 'name']);

            return view('provider.users.index', compact('users'));
        }

        abort(403);
    }

    public function create()
    {
        $this->ensureAdmin();

        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:25',
            'city' => 'nullable|string|max:100',
            'role' => 'required|in:admin,provider,customer',
            'status' => 'required|in:active,suspended',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create($validated);

            if ($user->role === 'provider') {
                $this->approveProviderProfile($user);
            }
        });

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }

    public function show(User $user)
    {
        $role = $this->activeRole();

        if ($role === 'admin') {
            $user->load('providerProfile');

            return view('admin.users.show', compact('user'));
        } elseif ($role === 'provider') {
            $user = $this->providerCustomers()
                ->select('id', 'name')
                ->findOrFail($user->id);

            return view('provider.users.show', compact('user'));
        }

        abort(403);
    }

    public function edit(User $user)
    {
        $this->ensureAdmin();

        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'phone' => 'nullable|string|max:25',
            'city' => 'nullable|string|max:100',
            'role' => 'required|in:admin,provider,customer',
            'status' => 'required|in:active,suspended',
        ]);

        if ($this->activeUser()->is($user)) {
            abort_if(
                $validated['role'] !== 'admin' || $validated['status'] !== 'active',
                403,
                'You cannot change your own role or suspend your account.'
            );
        }

        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        DB::transaction(function () use ($user, $validated) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $user->fill($validated);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $roleChanged = $user->isDirty('role');
            $user->save();

            if ($roleChanged && $user->role === 'provider') {
                $this->approveProviderProfile($user);
            } elseif ($roleChanged) {
                $user->providerProfile()->delete();
            }
        });

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        $this->ensureAdmin();

        abort_if(
            $this->activeUser()->is($user),
            403,
            'You cannot delete your own account.'
        );

        DB::transaction(function () use ($user) {
            $user = User::lockForUpdate()->findOrFail($user->id);
            $user->providerProfile()->delete();
            $user->delete();
        });

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }

    public function account()
    {
        $user = $this->activeUser();
        $role = $user->role;

        if ($role === 'admin') {
            return redirect()->route('users.index');
        } elseif ($role === 'provider') {
            $user->load('providerProfile');

            return view('provider.profile.show', compact('user'));
        } elseif ($role === 'customer') {
            return view('customer.profile.show', compact('user'));
        }

        abort(403);
    }

    private function providerCustomers()
    {
        return User::where('role', 'customer')
            ->whereHas('bookings.studio', function ($query) {
                $query->where('provider_id', Auth::id());
            });
    }

    private function activeRole(): string
    {
        return $this->activeUser()->role;
    }

    private function activeUser(): User
    {
        $user = Auth::user();

        if (!$user instanceof User || $user->status !== 'active') {
            abort(403);
        }

        return $user;
    }

    private function ensureAdmin(): void
    {
        abort_unless($this->activeRole() === 'admin', 403);
    }

    private function approveProviderProfile(User $user): void
    {
        $profile = $user->providerProfile()->withTrashed()->firstOrNew();

        if (!$profile->exists) {
            $profile->company_name = Str::limit($user->name, 150, '');
            $slug = Str::slug($profile->company_name);
            $profile->slug = Str::limit($slug ?: 'provider', 150, '') . '-' . $user->id;
            $profile->city = $user->city;
        }

        $profile->status = 'approved';
        $profile->admin_note = null;

        if ($profile->trashed()) {
            $profile->restore();
        } else {
            $profile->save();
        }
    }
}