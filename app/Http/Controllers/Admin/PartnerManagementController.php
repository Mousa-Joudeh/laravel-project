<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class PartnerManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'created_at');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($sort, ['company_name', 'city', 'status', 'created_at'], true)
            ? $sort : 'created_at';
        $perPage = in_array((int) $request->query('per_page', 5), [5, 10, 25, 50], true)
            ? (int) $request->query('per_page', 5) : 5;
        $query = ProviderProfile::query()->with('user:id,name,email,phone,role,status');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($owner) use ($search) {
                        $owner->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }
        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('status', $status);
        }
        $partners = $query->orderBy($sort, $direction)->orderBy('id', 'desc')
            ->paginate($perPage)->withQueryString();
        $counts = [
            'all' => ProviderProfile::count(),
            'pending' => ProviderProfile::where('status', 'pending')->count(),
            'approved' => ProviderProfile::where('status', 'approved')->count(),
            'rejected' => ProviderProfile::where('status', 'rejected')->count(),
        ];

        return view('admin.partners.index', compact('partners', 'counts', 'search', 'status', 'sort', 'direction'));
    }

    public function review(Request $request, ProviderProfile $providerProfile): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('status') === 'rejected')],
        ]);

        DB::transaction(function () use ($providerProfile, $data) {
            $owner = User::lockForUpdate()->findOrFail($providerProfile->user_id);
            $profile = ProviderProfile::lockForUpdate()->findOrFail($providerProfile->id);
            abort_unless($profile->status === 'pending', 409, 'This request has already been reviewed.');
            if ($data['status'] === 'approved') {
                abort_unless($owner->role === 'customer' && $owner->status === 'active', 409, 'Only an active customer can become a provider.');
                $owner->update(['role' => 'provider']);
            }
            $profile->update(['status' => $data['status'], 'admin_note' => $data['admin_note'] ?? null]);
        });

        return response()->json(['message' => 'Partner review saved.']);
    }
}
