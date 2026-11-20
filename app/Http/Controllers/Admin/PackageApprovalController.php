<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackageApprovalController extends Controller
{
    public function review(Request $request, Package $package): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,inactive'],
            'admin_note' => ['nullable', 'string', 'max:255'],
        ]);

        $approved = $data['status'] === 'approved';

        DB::transaction(function () use ($package, $approved): void {
            $locked = Package::query()->lockForUpdate()->findOrFail($package->getKey());

            if ($approved) {
                $studio = $locked->studio;
                abort_unless($studio && $studio->status === 'approved' && $studio->is_active, 409, 'Approve the active studio first.');
                $provider = $studio->provider;
                abort_unless($provider && $provider->status === 'active' && $provider->providerProfile?->status === 'approved', 409, 'Approve the active partner first.');
            }

            $locked->update(['is_active' => $approved]);
        });

        Log::info('Admin package publication changed', [
            'package_id' => $package->getKey(),
            'admin_id' => $request->user()->getKey(),
            'is_active' => $approved,
            'note' => $data['admin_note'] ?? null,
        ]);

        return response()->json([
            'message' => $approved ? 'Package activated.' : 'Package unpublished.',
        ]);
    }
}
