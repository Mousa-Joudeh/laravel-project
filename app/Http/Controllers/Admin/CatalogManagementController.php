<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Package;
use App\Models\Service;
use App\Models\Studio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogManagementController extends Controller
{
    public function index(Request $request): View
    {
        $type = (string) $request->query('type', 'studios');
        $type = in_array($type, ['studios', 'services', 'packages'], true) ? $type : 'studios';
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'created_at');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = in_array((int) $request->query('per_page', 5), [5, 10, 25, 50], true)
            ? (int) $request->query('per_page', 5) : 5;
        $categoryId = (int) $request->query('category', 0);
        $allowedSort = [
            'studios' => ['name', 'city', 'hourly_price', 'status', 'created_at'],
            'services' => ['title', 'price', 'status', 'created_at'],
            'packages' => ['name', 'price', 'created_at', 'is_active'],
        ];
        $sort = in_array($sort, $allowedSort[$type], true) ? $sort : 'created_at';
        $models = ['studios' => Studio::class, 'services' => Service::class, 'packages' => Package::class];
        $relations = ['studios' => ['provider:id,name', 'category:id,name'], 'services' => ['studio:id,name,provider_id,status,is_active'], 'packages' => ['studio:id,name,provider_id,status,is_active', 'studio.provider:id,name,status', 'studio.provider.providerProfile:user_id,status']];
        $model = $models[$type];
        $query = $model::query()->with($relations[$type]);
        if ($search !== '') {
            $column = $type === 'services' ? 'title' : 'name';
            $query->where(function ($q) use ($search, $column, $type) {
                $q->where($column, 'like', "%{$search}%");
                if ($type === 'studios') {
                    $q->orWhere('city', 'like', "%{$search}%")
                        ->orWhereHas('provider', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                } else {
                    $q->orWhereHas('studio', fn ($s) => $s->where('name', 'like', "%{$search}%"));
                }
            });
        }
        if ($type === 'studios' && $categoryId > 0) {
            $query->where('category_id', $categoryId);
        }
        if (in_array($status, ['draft', 'pending', 'approved', 'rejected'], true) && $type !== 'packages') {
            $query->where('status', $status);
        }
        if ($type === 'packages' && in_array($status, ['pending', 'inactive', 'approved', 'active'], true)) {
            $query->where('is_active', in_array($status, ['approved', 'active'], true));
        }
        $records = $query->orderBy($sort, $direction)->orderBy('id', 'desc')
            ->paginate($perPage)->withQueryString();
        $counts = [
            'studios' => Studio::count(),
            'services' => Service::count(),
            'packages' => Package::count(),
            'pending_studios' => Studio::where('status', 'pending')->count(),
            'pending_services' => Service::where('status', 'pending')->count(),
            'pending_packages' => Package::where('is_active', false)->count(),
        ];
        $categories = Category::orderBy('name')->get(['id', 'name']);

        return view('admin._workspace.catalog.index', compact('records', 'counts', 'categories', 'type', 'search', 'status', 'sort', 'direction', 'categoryId'));
    }

    public function reviewStudio(Request $request, Studio $studio): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('status') === 'rejected')],
        ]);
        $studio->update($data);

        return response()->json(['message' => 'Studio review updated.']);
    }

    public function reviewService(Request $request, Service $service): JsonResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:255', Rule::requiredIf($request->input('status') === 'rejected')],
        ]);
        $service->update($data);

        return response()->json(['message' => 'Service review updated.']);
    }
}
