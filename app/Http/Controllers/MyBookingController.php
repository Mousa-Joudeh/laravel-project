<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MyBookingController extends Controller
{
    
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'upcoming');
        if ($tab !== 'past') {
            $tab = 'upcoming';
        }

        $bookings = Booking::with(['studio.images', 'studio.provider.providerProfile', 'items', 'review'])
            ->where('customer_id', Auth::id())
            ->orderBy('starts_at')
            ->get();

        $upcoming = [];
        $past = [];

        foreach ($bookings as $booking) {
            $booking->cover_url = $booking->studio->coverUrl();

            $isClosed = $booking->status == 'cancelled' || $booking->status == 'expired';

            
            $booking->can_cancel = $booking->starts_at > now()
                && ($booking->status == 'pending_payment' || $booking->status == 'confirmed');

            
            $booking->can_rate = $booking->ends_at < now() && !$isClosed;

            if ($booking->ends_at > now() && !$isClosed) {
                $upcoming[] = $booking;
            } else {
                $past[] = $booking;
            }
        }

        
        $past = array_reverse($past);

        if ($tab == 'past') {
            $list = $past;
        } else {
            $list = $upcoming;
        }

        return view('my-bookings.index', compact('tab', 'upcoming', 'past', 'list'));
    }

    
    public function show(string $id)
    {
        $booking = Booking::with(['studio.images', 'studio.provider.providerProfile', 'items', 'transactions', 'review'])
            ->findOrFail($id);

        if ($booking->customer_id !== Auth::id()) {
            abort(403);
        }

        $isClosed = $booking->status == 'cancelled' || $booking->status == 'expired';

        $booking->can_cancel = $booking->starts_at > now()
            && ($booking->status == 'pending_payment' || $booking->status == 'confirmed');

        $booking->can_rate = $booking->ends_at < now() && !$isClosed;

        $coverUrl = $booking->studio->coverUrl();

        return view('my-bookings.show', compact('booking', 'coverUrl'));
    }

    
    public function cancel(string $id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->customer_id !== Auth::id()) {
            abort(403);
        }

        if ($booking->starts_at < now() || ($booking->status != 'pending_payment' && $booking->status != 'confirmed')) {
            return back()->with('error', 'This booking can not be cancelled.');
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        return redirect()->route('my-bookings.index')
            ->with('success', 'Booking ' . $booking->reference . ' was cancelled.');
    }

    
    public function review(Request $request, string $id)
    {
        $booking = Booking::with('review')->findOrFail($id);

        if ($booking->customer_id !== Auth::id()) {
            abort(403);
        }

        if ($booking->ends_at > now() || $booking->status == 'cancelled' || $booking->status == 'expired') {
            return back()->with('error', 'You can rate a session only after it ends.');
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        if ($booking->review) {
            $booking->review->update($validated);
        } else {
            $validated['booking_id'] = $booking->id;
            Review::create($validated);
        }

        return redirect()->route('my-bookings.show', $booking->id)
            ->with('success', 'Thanks! Your review was saved.');
    }
}