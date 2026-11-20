<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\ProviderProfile;
use App\Models\Service;
use App\Models\Studio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ApprovalQueueController extends Controller
{
    public function index(Request $request): View
    {
        $type = (string) $request->query('type', 'partners');
        $models = [
            'partners' => ProviderProfile::class,
            'studios' => Studio::class,
            'services' => Service::class,
            'packages' => Package::class,
        ];
        $type = array_key_exists($type, $models) ? $type : 'partners';
        $status = (string) $request->query('status', 'pending');
        $allowedStatuses = $type === 'packages'
            ? ['pending', 'approved', 'all']
            : ['pending', 'approved', 'rejected', 'all'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'pending';
        $search = trim((string) $request->query('search', ''));
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $sort = in_array($request->query('sort'), ['created_at', 'status'], true)
            ? $request->query('sort') : 'created_at';
        $perPage = in_array((int) $request->query('per_page', 5), [5, 10, 25, 50], true)
            ? (int) $request->query('per_page', 5) : 5;

        $relations = [
            'partners' => ['user:id,name,email,role,status'],
            'studios' => ['provider:id,name', 'category:id,name'],
            'services' => ['studio:id,name,provider_id', 'studio.provider:id,name'],
            'packages' => ['studio:id,name,provider_id', 'studio.provider:id,name'],
        ];
        $query = $models[$type]::query()->with($relations[$type]);
        if ($status !== 'all') {
            if ($type === 'packages') {
                $query->where('is_active', $status === 'approved');
            } else {
                $query->where('status', $status);
            }
        }
        if ($search !== '') {
            $column = $type === 'partners' ? 'company_name' : ($type === 'services' ? 'title' : 'name');
            $query->where(function ($q) use ($type, $search, $column): void {
                $q->where($column, 'like', "%{$search}%");
                if ($type === 'partners') {
                    $q->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                } elseif ($type === 'studios') {
                    $q->orWhereHas('provider', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                } else {
                    $q->orWhereHas('studio', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                }
            });
        }
        $records = $query->orderBy($type === 'packages' && $sort === 'status' ? 'is_active' : $sort, $direction)->orderByDesc('id')
            ->paginate($perPage)->withQueryString();

        $counts = [
            'partners' => ProviderProfile::where('status', 'pending')->count(),
            'studios' => Studio::where('status', 'pending')->count(),
            'services' => Service::where('status', 'pending')->count(),
            'packages' => Package::where('is_active', false)->count(),
        ];

        return view('admin._workspace.approvals.index', compact(
            'type', 'status', 'search', 'direction', 'sort', 'records', 'counts'
        ));
    }
}
