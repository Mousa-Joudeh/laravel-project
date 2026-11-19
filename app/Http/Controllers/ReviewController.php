<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $reviews = Review::with(['booking.customer', 'booking.studio'])
                ->latest()
                ->paginate(15);

            return view('admin.reviews.index', compact('reviews'));

        } elseif ($role === 'provider') {

            $reviews = Review::with(['booking.customer', 'booking.studio'])
                ->whereHas('booking.studio', function ($query) {
                    $query->where('provider_id', auth()->id());
                })
                ->latest()
                ->paginate(15);

            return view('provider.reviews.index', compact('reviews'));

        } elseif ($role === 'customer') {

            $reviews = Review::with(['booking.customer', 'booking.studio'])
                ->whereHas('booking', function ($query) {
                    $query->where('customer_id', auth()->id());
                })
                ->latest()
                ->paginate(15);

            return view('customer.reviews.index', compact('reviews'));
        }

        abort(403);
    }

    public function create()
    {
        $role = auth()->user()->role;

        // Only customers write reviews
        if ($role !== 'customer') {
            abort(403);
        }

        $bookings = Booking::with('studio')
            ->where('customer_id', auth()->id())
            ->where('status', 'completed')
            ->whereDoesntHave('review')
            ->get();

        return view('customer.reviews.create', compact('bookings'));
    }

    public function store(Request $request)
    {
        $role = auth()->user()->role;

        if ($role !== 'customer') {
            abort(403);
        }

        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $booking = Booking::findOrFail($validated['booking_id']);

        if ($booking->customer_id !== auth()->id()) {
            abort(403);
        }

        if ($booking->status !== 'completed') {
            return back()->with('error', 'You can only review completed bookings.');
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            return back()->with('error', 'This booking is already reviewed.');
        }

        Review::create([
            'booking_id' => $booking->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return redirect()->route('reviews.index')
            ->with('success', 'Review created successfully!');
    }

    public function show(Review $review)
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $review = Review::with(['booking.customer', 'booking.studio'])
                ->findOrFail($review->booking_id);

            return view('admin.reviews.show', compact('review'));

        } elseif ($role === 'provider') {

            $review = Review::with(['booking.customer', 'booking.studio'])
                ->whereHas('booking.studio', function ($query) {
                    $query->where('provider_id', auth()->id());
                })
                ->findOrFail($review->booking_id);

            return view('provider.reviews.show', compact('review'));

        } elseif ($role === 'customer') {

            $review = Review::with(['booking.customer', 'booking.studio'])
                ->whereHas('booking', function ($query) {
                    $query->where('customer_id', auth()->id());
                })
                ->findOrFail($review->booking_id);

            return view('customer.reviews.show', compact('review'));
        }

        abort(403);
    }

    public function edit(Review $review)
    {
        $role = auth()->user()->role;

        if ($role !== 'customer') {
            abort(403);
        }

        $review = Review::with(['booking.studio'])
            ->whereHas('booking', function ($query) {
                $query->where('customer_id', auth()->id());
            })
            ->findOrFail($review->booking_id);

        return view('customer.reviews.edit', compact('review'));
    }

    public function update(Request $request, Review $review)
    {
        $role = auth()->user()->role;

        if ($role !== 'customer') {
            abort(403);
        }

        $review = Review::whereHas('booking', function ($query) {
            $query->where('customer_id', auth()->id());
        })->findOrFail($review->booking_id);

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return redirect()->route('reviews.index')
            ->with('success', 'Review updated successfully!');
    }

    public function destroy(Review $review)
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $review = Review::findOrFail($review->booking_id);

        } elseif ($role === 'customer') {

            $review = Review::whereHas('booking', function ($query) {
                $query->where('customer_id', auth()->id());
            })->findOrFail($review->booking_id);

        } else {
            abort(403);
        }

        $review->delete();

        return redirect()->route('reviews.index')
            ->with('success', 'Review deleted successfully!');
    }
}