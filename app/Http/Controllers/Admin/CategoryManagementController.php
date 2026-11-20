<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Studio;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CategoryManagementController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $sort = (string) $request->query('sort', 'created_at');
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';
        $perPage = in_array((int) $request->query('per_page', 5), [5, 10, 25, 50], true)
            ? (int) $request->query('per_page', 5) : 5;
        $sort = in_array($sort, ['name', 'created_at', 'studios_count', 'is_active'], true)
            ? $sort : 'created_at';

        $query = Category::query()->withCount('studios');
        if ($search !== '') {
            $query->where('name', 'like', "%{$search}%");
        }
        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('is_active', $status === 'active');
        }
        $categories = $query->orderBy($sort, $direction)->orderBy('id', 'desc')
            ->paginate($perPage)->withQueryString();
        $counts = [
            'all' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
        ];

        return view('admin._workspace.categories.index', compact('categories', 'counts', 'search', 'status', 'sort', 'direction'));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCategory($request);
        $data['slug'] = $this->slug($data['name']);
        $category = Category::create($data);

        return response()->json(['message' => 'Category created.', 'id' => $category->id], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $this->validateCategory($request);
        if ($data['name'] !== $category->name) {
            $data['slug'] = $this->slug($data['name']);
        }
        $category->update($data);

        return response()->json(['message' => 'Category updated.']);
    }

    public function destroy(Category $category): JsonResponse
    {
        if (Studio::withTrashed()->where('category_id', $category->id)->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category is used by studios. Deactivate it instead.',
            ]);
        }
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }

    private function validateCategory(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
            'img' => ['nullable', 'string', 'max:500'],
        ]);
        $data['img'] = $data['img'] ?? null;

        return $data;
    }

    private function slug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $base = Str::limit($base, 100, '');

        do {
            $slug = $base . '-' . Str::lower(Str::random(7));
        } while (Category::withTrashed()->where('slug', $slug)->exists());

        return $slug;
    }
}
