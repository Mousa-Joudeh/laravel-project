<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CategoryController extends Controller
{
    public function index(): View
    {
        $query = Category::query();

        if (auth()->user()?->role !== 'admin') {
            $query->where('is_active', true);
        }

        $categories = $query
            ->orderBy('name')
            ->paginate(15);

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        $this->ensureAdmin();

        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'img' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $data = [
            'name' => $validated['name'],
            'slug' => $this->generateUniqueSlug($validated['name']),
            'is_active' => $request->boolean('is_active', true),
            'img' => null,
        ];

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')
                ->store('categories', 'public');
        }

        try {
            Category::create($data);
        } catch (Throwable $exception) {
            // حذف الصورة الجديدة إذا فشل حفظ السجل.
            if ($data['img'] !== null) {
                Storage::disk('public')->delete($data['img']);
            }

            throw $exception;
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Category $category): View
    {
        if (
            auth()->user()?->role !== 'admin'
            && !$category->is_active
        ) {
            abort(404);
        }

        return view('categories.show', compact('category'));
    }

    public function edit(Category $category): View
    {
        $this->ensureAdmin();

        return view('categories.edit', compact('category'));
    }

    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
            'img' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $data = [
            'name' => $validated['name'],
        ];

        if (array_key_exists('is_active', $validated)) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $oldImage = $category->img;
        $newImage = null;

        if ($request->hasFile('img')) {
            $newImage = $request->file('img')
                ->store('categories', 'public');

            $data['img'] = $newImage;
        }

        try {
            $category->update($data);
        } catch (Throwable $exception) {
            // حذف الصورة الجديدة إذا فشل التعديل.
            if ($newImage !== null) {
                Storage::disk('public')->delete($newImage);
            }

            throw $exception;
        }

        // حذف الصورة القديمة بعد نجاح حفظ البديلة.
        if ($newImage !== null && $oldImage !== null) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->ensureAdmin();

        // يشمل الاستوديوهات المحذوفة منطقيًا أيضًا.
        if ($category->studios()->withTrashed()->exists()) {
            return redirect()->route('categories.index')
                ->with(
                    'error',
                    'Cannot delete a category linked to studios.'
                );
        }

        // Soft Delete: نحتفظ بالصورة مع السجل.
        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully.');
    }

    private function ensureAdmin(): void
    {
        if (auth()->user()?->role !== 'admin') {
            abort(403);
        }
    }

    private function generateUniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'category';
        }

        $baseSlug = Str::substr($baseSlug, 0, 100);

        $slug = $baseSlug;
        $suffix = 1;

        // نتجنب تكرار slug حتى مع السجلات المحذوفة منطقيًا.
        while (
            Category::withTrashed()
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }
}