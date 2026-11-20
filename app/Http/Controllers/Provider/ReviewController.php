<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $mine = fn () => Review::whereHas('booking.studio', fn ($q) => $q->where('provider_id', auth()->id()));

        $stars = $mine()
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');

        $count = (int) $stars->sum();

        $summary = [
            'count' => $count,
            'avg'   => $count ? round((float) $mine()->avg('rating'), 1) : 0,
            'stars' => $stars->all(),
        ];

        $reviews = $mine()
            ->with(['booking.customer', 'booking.studio', 'booking.items'])
            ->when($request->filled('rating'), fn ($q) => $q->where('rating', (int) $request->input('rating')))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('provider.panel.reviews.index', compact('reviews', 'summary'));
    }
}
