<?php

namespace App\Http\Controllers;

use App\Models\Studio;
use App\Models\StudioImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StudioimageController extends Controller
{
    
    public function index(Request $request)
    {
        $studio = Studio::findOrFail($request->studio_id);

        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $isOwner = Auth::check() && Auth::id() === $studio->provider_id;

        if ($studio->status !== 'approved' || !$studio->is_active) {
            if (!$isAdmin && !$isOwner) {
                abort(404);
            }
        }

        $images = StudioImage::where('studio_id', $studio->id)
            ->orderBy('sort_order')
            ->get();

        return view('studioimages.index', compact('studio', 'images'));
    }

   
    public function create(Request $request)
    {
        $studio = Studio::findOrFail($request->studio_id);

        if (!Auth::check() || Auth::id() !== $studio->provider_id) {
            abort(403, 'Only the owner of the studio can add images.');
        }

        return view('studioimages.create', compact('studio'));
    }

    
    public function store(Request $request)
    {
        $request->validate([
            'studio_id' => 'required|exists:studios,id',
            'images' => 'required|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $studio = Studio::findOrFail($request->studio_id);

        if (!Auth::check() || Auth::id() !== $studio->provider_id) {
            abort(403, 'Only the owner of the studio can add images.');
        }

        
        $hasCover = StudioImage::where('studio_id', $studio->id)
            ->where('is_cover', true)
            ->exists();

        
        $order = StudioImage::where('studio_id', $studio->id)->count();

        foreach ($request->file('images') as $file) {
            $order++;

            StudioImage::create([
                'studio_id' => $studio->id,
                'image_path' => $file->store('studios', 'public'),
                'is_cover' => !$hasCover,
                'sort_order' => $order,
            ]);

            
            $hasCover = true;
        }

        return redirect()->route('studioimages.index', ['studio_id' => $studio->id])
            ->with('status', 'Images uploaded.');
    }

    
    public function show(string $id)
    {
        $image = StudioImage::with('studio')->findOrFail($id);
        $studio = $image->studio;

        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $isOwner = Auth::check() && Auth::id() === $studio->provider_id;

        if ($studio->status !== 'approved' || !$studio->is_active) {
            if (!$isAdmin && !$isOwner) {
                abort(404);
            }
        }

        return view('studioimages.show', compact('image', 'studio'));
    }

    
    public function edit(string $id)
    {
        $image = StudioImage::with('studio')->findOrFail($id);

        if (!Auth::check() || Auth::id() !== $image->studio->provider_id) {
            abort(403, 'Only the owner of the studio can edit images.');
        }

        return view('studioimages.edit', compact('image'));
    }

    
    public function update(Request $request, string $id)
    {
        $image = StudioImage::with('studio')->findOrFail($id);

        if (!Auth::check() || Auth::id() !== $image->studio->provider_id) {
            abort(403, 'Only the owner of the studio can edit images.');
        }

        $validated = $request->validate([
            'sort_order' => 'required|integer|min:0',
            'is_cover' => 'nullable|boolean',
        ]);

        $validated['is_cover'] = $request->has('is_cover');

        if ($validated['is_cover']) {
            StudioImage::where('studio_id', $image->studio_id)->update(['is_cover' => false]);
        }

        $image->update($validated);

        return redirect()->route('studioimages.index', ['studio_id' => $image->studio_id])
            ->with('status', 'Image updated.');
    }

    
    public function destroy(string $id)
    {
        $image = StudioImage::with('studio')->findOrFail($id);

        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $isOwner = Auth::check() && Auth::id() === $image->studio->provider_id;

        if (!$isAdmin && !$isOwner) {
            abort(403, 'You cannot delete this image.');
        }

        $studioId = $image->studio_id;

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return redirect()->route('studioimages.index', ['studio_id' => $studioId])
            ->with('status', 'Image deleted.');
    }
}