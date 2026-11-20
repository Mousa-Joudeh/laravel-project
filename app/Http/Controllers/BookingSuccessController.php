<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Support\Facades\Auth;


class BookingSuccessController extends Controller
{
    public function show(string $id)
    {
        $booking = Booking::with(['studio.images'])->findOrFail($id);

        
        if (!Auth::check() || Auth::id() !== $booking->customer_id) {
            abort(403);
        }

        
        $cover = $booking->studio->images->first();
        if ($cover) {
            $coverUrl = asset('storage/' . $cover->image_path);
        } else {
            $coverUrl = asset('images/placeholder.svg');
        }

        return view('booking-success.show', compact('booking', 'coverUrl'));
    }
}