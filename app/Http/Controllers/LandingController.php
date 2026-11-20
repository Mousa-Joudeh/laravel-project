<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
use App\Models\ProviderProfile;
use App\Models\Review;
use App\Models\Studio;
use App\Models\StudioType;

class LandingController extends Controller
{
   
    public function index()
    {
        
        $studios = Studio::with(['images', 'type', 'reviews'])
            ->where('status', 'approved')
            ->where('is_active', true)
            ->get();

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

        $topSpaces = $studios->sortByDesc('rating')->take(5);

        
        $packages = Package::with(['images', 'studio.images', 'bookingItems'])
            ->where('is_active', true)
            ->get();

        foreach ($packages as $package) {
            
            $bookingIds = $package->bookingItems->pluck('booking_id');
            $reviews = Review::whereIn('booking_id', $bookingIds)->get();
            $package->rating = round($reviews->avg('rating'), 1);
            $package->reviews_total = $reviews->count();

            
            $image = $package->images->first();
            if (!$image) {
                $image = $package->studio->images->first();
            }

            if ($image) {
                $package->cover_url = asset('storage/' . $image->image_path);
            } else {
                $package->cover_url = asset('images/placeholder.svg');
            }
        }

        $topPackages = $packages->sortByDesc('rating')->take(3);

        
        $studioTypes = StudioType::with('studios')->orderBy('sort_order')->get();

        foreach ($studioTypes as $type) {
            $type->studios_count = $type->studios
                ->where('status', 'approved')
                ->where('is_active', true)
                ->count();
        }

        
        $testimonials = Review::with(['booking.customer', 'booking.studio'])
            ->where('rating', 5)
            ->whereNotNull('comment')
            ->latest()
            ->take(3)
            ->get();

        
        $stats = [
            'spaces' => Studio::where('status', 'approved')->where('is_active', true)->count(),
            'studios' => ProviderProfile::where('status', 'approved')->count(),
            'bookings' => Booking::where('status', 'completed')->count(),
            'rating' => round(Review::avg('rating'), 1),
        ];

        $cities = ['Amman', 'Irbid', 'Zarqa', 'Aqaba', 'Salt', 'Madaba', 'Jerash', 'Karak', 'Mafraq', 'Ajloun'];

        return view('landing.index', compact('topSpaces', 'topPackages', 'studioTypes', 'testimonials', 'stats', 'cities'));
    }
}
