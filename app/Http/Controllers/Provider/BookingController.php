<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ScopesToProvider;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ScopesToProvider;

    private function mine()
    {
        return Booking::whereHas('studio', fn ($q) => $q->where('provider_id', auth()->id()));
    }

    public function index(Request $request)
    {
        $counts = $this->mine()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $bookings = $this->mine()
            ->with(['customer', 'studio', 'items'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->when($request->input('when') === 'upcoming', fn ($q) => $q->where('starts_at', '>=', now()))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';

                $q->where(function ($q) use ($term) {
                    $q->where('reference', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->orderByDesc('starts_at')
            ->paginate(12)
            ->withQueryString();

        return view('provider.panel.bookings.index', compact('bookings', 'counts'));
    }

    public function show(Booking $booking)
    {
        $this->authorizeBooking($booking);

        $booking->load(['customer', 'studio', 'items', 'transactions', 'review']);

        return view('provider.panel.bookings.show', compact('booking'));
    }

    public function update(Request $request, Booking $booking)
    {
        $this->authorizeBooking($booking);

        $data = $request->validate(['status' => 'required|in:completed,cancelled']);

        if ($booking->status !== 'confirmed') {
            return back()->withErrors(['status' => 'Only confirmed bookings can be changed.']);
        }

        $booking->status = $data['status'];

        if ($data['status'] === 'cancelled') {
            $booking->cancelled_at = now();
        }

        $booking->save();

        return back()->with('status', 'Booking ' . $booking->reference . ' marked as ' . $data['status'] . '.');
    }

    private function authorizeBooking(Booking $booking): void
    {
        abort_unless((int) $booking->studio?->provider_id === (int) auth()->id(), 404);
    }
}
