<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $categories = Category::latest()->paginate(15);

            return view('admin.categories.index', compact('categories'));

        } elseif ($role === 'provider') {

            $categories = Category::where('is_active', true)
                ->latest()
                ->paginate(15);

            return view('provider.categories.index', compact('categories'));

        } elseif ($role === 'customer') {

            $categories = Category::where('is_active', true)
                ->latest()
                ->paginate(15);

            return view('customer.categories.index', compact('categories'));
        }

        abort(403);
    }

    public function create()
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'is_active' => 'nullable|boolean',
            'img' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('categories', 'public');
        }

        Category::create($data);

        return redirect()->route('categories.index')
            ->with('success', 'Category created successfully!');
    }

    public function show(Category $category)
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $category = Category::findOrFail($category->id);

            return view('admin.categories.show', compact('category'));

        } elseif ($role === 'provider') {

            $category = Category::where('is_active', true)
                ->findOrFail($category->id);

            return view('provider.categories.show', compact('category'));

        } elseif ($role === 'customer') {

            $category = Category::where('is_active', true)
                ->findOrFail($category->id);

            return view('customer.categories.show', compact('category'));
        }

        abort(403);
    }

    public function edit(Category $category)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $category = Category::findOrFail($category->id);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $category = Category::findOrFail($category->id);

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,' . $category->id,
            'is_active' => 'nullable|boolean',
            'img' => 'nullable|image|max:2048',
        ]);

        $data = [
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->hasFile('img')) {
            $data['img'] = $request->file('img')->store('categories', 'public');
        }

        $category->update($data);

        return redirect()->route('categories.index')
            ->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403);
        }

        $category = Category::findOrFail($category->id);

        $category->delete();

        return redirect()->route('categories.index')
            ->with('success', 'Category deleted successfully!');
    }
}