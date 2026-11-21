<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Studio;
use App\Models\StudioType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Service;
class StudioController extends Controller
{
    
     public function index(Request $request)
    {
       
        if (Auth::check() && Auth::user()->role === 'admin') {
            $query = Studio::with(['type', 'images', 'reviews', 'provider']);
        } elseif (Auth::check() && Auth::user()->role === 'provider') {
            $query = Studio::with(['type', 'images', 'reviews'])
                ->where('provider_id', Auth::id());
        } else {
            $query = Studio::with(['type', 'images', 'reviews'])
                ->where('status', 'approved')
                ->where('is_active', true);
        }

        
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('city', 'like', '%' . $search . '%');
            });
        }

        
        if ($request->filled('type')) {
            $query->where('studio_type_id', $request->type);
        }

        
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        
        if ($request->filled('capacity')) {
            $query->where('capacity', '>=', $request->capacity);
        }

        
        if ($request->sort === 'price_asc') {
            $query->orderBy('hourly_price', 'asc');
        } elseif ($request->sort === 'price_desc') {
            $query->orderBy('hourly_price', 'desc');
        } else {
            $query->latest();
        }

        
        $studios = $query->paginate(9)->withQueryString();

        
        foreach ($studios as $studio) {
            $studio->rating = round($studio->reviews->avg('rating'), 1);
            $studio->reviews_total = $studio->reviews->count();

            $cover = $studio->images->where('is_cover', true)->first();
            if (!$cover) {
                $cover = $studio->images->first();
            }

            if ($cover) {
                $studio->cover_url = asset('storage/' . $cover->image_path);
            } else {
                $studio->cover_url = asset('images/placeholder.svg');
            }
        }

        
        $studioTypes = StudioType::orderBy('sort_order')->get();
        $cities = ['Amman', 'Irbid', 'Zarqa', 'Aqaba', 'Salt', 'Madaba', 'Jerash', 'Karak', 'Mafraq', 'Ajloun'];

        return view('studios.index', compact('studios', 'studioTypes', 'cities'));
    }
    
    public function create()
    {
        if (!Auth::check() || Auth::user()->role !== 'provider') {
            abort(403, 'Only providers can add studios.');
        }

        $categories = Category::where('is_active', true)->get();
        $studioTypes = StudioType::orderBy('sort_order')->get();

        return view('studios.create', compact('categories', 'studioTypes'));
    }

    
    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'provider') {
            abort(403, 'Only providers can add studios.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'required|exists:categories,id',
            'studio_type_id' => 'required|exists:studio_types,id',
            'description' => 'required|string',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'area_sqm' => 'nullable|numeric|min:1',
            'hourly_price' => 'required|numeric|min:1',
            'facilities' => 'nullable|array',
            'included_equipment' => 'nullable|array',
        ]);

        $validated['slug'] = Str::slug($request->name) . '-' . time();
        $validated['status'] = 'pending';
        $validated['is_active'] = true;
        $request->user()->studios()->create($validated);

        return redirect()->route('studios.index')
            ->with('status', 'Your studio was sent to the admin for approval.');
    }

   
    public function show(string $id)
    {
        $studio = Studio::with(['category', 'type', 'provider.providerProfile', 'images', 'services', 'packages', 'reviews.booking.customer'])
            ->findOrFail($id);

        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $isOwner = Auth::check() && Auth::id() === $studio->provider_id;

        if ($studio->status !== 'approved' || !$studio->is_active) {
            if (!$isAdmin && !$isOwner) {
                abort(404);
            }
        }

        
        $reviews = $studio->reviews->sortByDesc('created_at');
        $rating = round($reviews->avg('rating'), 1);
        $reviewsTotal = $reviews->count();
        $addons = Service::where('studio_id', $studio->id)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->whereIn('kind', ['addon', 'both'])
            ->get();
         return view('studios.show', compact('studio', 'reviews', 'rating', 'reviewsTotal', 'addons'));
    }

   
    public function edit(string $id)
    {
        $studio = Studio::findOrFail($id);

        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $isOwner = Auth::check() && Auth::id() === $studio->provider_id;

        if (!$isAdmin && !$isOwner) {
            abort(403, 'You cannot edit this studio.');
        }

        $categories = Category::where('is_active', true)->get();
        $studioTypes = StudioType::orderBy('sort_order')->get();

        return view('studios.edit', compact('studio', 'categories', 'studioTypes'));
    }

    
    public function update(Request $request, string $id)
    {
        $studio = Studio::findOrFail($id);

        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $isOwner = Auth::check() && Auth::id() === $studio->provider_id;

        if (!$isAdmin && !$isOwner) {
            abort(403, 'You cannot edit this studio.');
        }

        
        if ($isAdmin) {
            $validated = $request->validate([
                'status' => 'required|in:pending,approved,rejected',
                'admin_note' => 'nullable|string|max:255',
            ]);

            $studio->update($validated);

            return redirect()->route('studios.index')
                ->with('status', 'Studio status updated.');
        }

       
        $validated = $request->validate([
            'name' => 'required|string|max:150',
            'category_id' => 'required|exists:categories,id',
            'studio_type_id' => 'required|exists:studio_types,id',
            'description' => 'required|string',
            'city' => 'required|string|max:100',
            'address' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'area_sqm' => 'nullable|numeric|min:1',
            'hourly_price' => 'required|numeric|min:1',
            'facilities' => 'nullable|array',
            'included_equipment' => 'nullable|array',
            'is_active' => 'nullable|boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        
        if ($studio->status === 'rejected') {
            $validated['status'] = 'pending';
        }

        $studio->update($validated);

        return redirect()->route('studios.show', $studio->id)
            ->with('status', 'Studio updated.');
    }

    
    public function destroy(string $id)
    {
        $studio = Studio::findOrFail($id);

        $isAdmin = Auth::check() && Auth::user()->role === 'admin';
        $isOwner = Auth::check() && Auth::id() === $studio->provider_id;

        if (!$isAdmin && !$isOwner) {
            abort(403, 'You cannot delete this studio.');
        }

        if ($studio->bookings()->count() > 0) {
            return redirect()->route('studios.index')
                ->with('error', 'This studio has bookings, you cannot delete it.');
        }

        $studio->delete();

        return redirect()->route('studios.index')
            ->with('status', 'Studio deleted.');
    }
}