<?php

namespace App\Http\Controllers;

use App\Models\Review;

class ProviderDashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        abort_unless($user->isProvider(), 403);

        $profile = $user->providerProfile;

        $bookings = $user->providerBookings();           // bookings made at this provider's studios
        $reviews  = Review::whereHas('booking.studio', fn ($q) => $q->where('provider_id', $user->id));

        /* ---------- Stat cards ---------- */
        $reviewCount = (clone $reviews)->count();

        $stats = [
            // Revenue = completed bookings. "Expected" = confirmed (paid) sessions still to happen.
            'revenue'  => (float) (clone $bookings)->where('bookings.status', 'completed')->sum('bookings.total_price'),
            'expected' => (float) (clone $bookings)->where('bookings.status', 'confirmed')->where('bookings.starts_at', '>=', now())->sum('bookings.total_price'),
            'bookings' => (clone $bookings)->count(),
            'upcoming' => (clone $bookings)->where('bookings.status', 'confirmed')->where('bookings.starts_at', '>=', now())->count(),
            'services' => $user->services()->count(),
            'spaces'   => $user->studios()->count(),
            'packages' => $user->packages()->count(),
            'reviews'  => $reviewCount,
            'rating'   => $reviewCount ? round((float) (clone $reviews)->avg('rating'), 1) : null,
        ];

        /* ---------- Waiting for admin approval ---------- */
        $pending = [
            'studios'  => $user->studios()->where('status', 'pending')->count(),
            'services' => $user->services()->where('services.status', 'pending')->count(),
        ];

        /* ---------- Chart: last 6 months ---------- */
        $months = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));

        $monthly = (clone $bookings)
            ->whereIn('bookings.status', ['confirmed', 'completed'])
            ->where('bookings.starts_at', '>=', $months->first())
            ->get(['bookings.status', 'bookings.starts_at', 'bookings.total_price'])
            ->groupBy(fn ($b) => $b->starts_at->format('Y-m'));

        $chart = [
            'labels'   => $months->map(fn ($m) => $m->format('M'))->all(),
            'bookings' => $months->map(fn ($m) => $monthly->get($m->format('Y-m'))?->count() ?? 0)->all(),
            'revenue'  => $months->map(fn ($m) => (float) ($monthly->get($m->format('Y-m'))?->where('status', 'completed')->sum('total_price') ?? 0))->all(),
        ];

        /* ---------- Lists ---------- */
        $upcoming = (clone $bookings)
            ->with(['customer', 'studio', 'items'])
            ->where('bookings.status', 'confirmed')
            ->where('bookings.starts_at', '>=', now())
            ->orderBy('bookings.starts_at')
            ->limit(5)
            ->get();

        $latestReviews = (clone $reviews)
            ->with(['booking.customer', 'booking.studio', 'booking.items'])
            ->latest()
            ->limit(4)
            ->get();

        return view('provider.dashboard', compact('profile', 'stats', 'pending', 'chart', 'upcoming', 'latestReviews'));
    }
}
