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

        // Booking dates on this page are displayed in Jordan local time.
        // Google Calendar interprets these local timestamps using the supplied timezone.
        // This link pre-fills an event; the customer must still confirm Save in Calendar.
        $calendarUrl = 'https://calendar.google.com/calendar/render?' . http_build_query([
            'action' => 'TEMPLATE',
            'text' => 'SPACE - ' . $booking->studio->name,
            'dates' => $booking->starts_at->format('Ymd\THis') . '/' . $booking->ends_at->format('Ymd\THis'),
            'ctz' => 'Asia/Amman',
            'details' => 'Booking reference: ' . $booking->reference,
            'location' => $booking->studio->city ?: 'Amman',
        ], '', '&', PHP_QUERY_RFC3986);

        return view('booking-success.show', compact('booking', 'coverUrl', 'calendarUrl'));
    }
}