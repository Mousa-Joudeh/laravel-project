<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $bookings = Booking::with(['customer', 'studio'])
                ->latest()
                ->paginate(15);

            return view('admin.bookings.index', compact('bookings'));

        } elseif ($role === 'provider') {

            $bookings = Booking::with(['customer', 'studio'])
                ->whereHas('studio', function ($query) {
                    $query->where('provider_id', auth()->id());
                })
                ->latest()
                ->paginate(15);

            return view('provider.bookings.index', compact('bookings'));

        } elseif ($role === 'customer') {

            $bookings = Booking::with(['customer', 'studio'])
                ->where('customer_id', auth()->id())
                ->latest()
                ->paginate(15);

            return view('customer.bookings.index', compact('bookings'));
        }

        abort(403);
    }

    public function create()
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $studios = Studio::all();
            $customers = User::where('role', 'customer')->get();

            return view('admin.bookings.create', compact('studios', 'customers'));

        } elseif ($role === 'provider') {

            $studios = Studio::where('provider_id', auth()->id())->get();
            $customers = User::where('role', 'customer')->get();

            return view('provider.bookings.create', compact('studios', 'customers'));

        } elseif ($role === 'customer') {

            $studios = Studio::where('is_active', true)->get();

            return view('customer.bookings.create', compact('studios'));
        }

        abort(403);
    }

    public function store(Request $request)
    {
        $role = auth()->user()->role;

        $rules = [
            'studio_id' => 'required|exists:studios,id',
            'mode' => 'required|in:space,service,package,custom',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
            'total_price' => 'required|numeric|min:0',
        ];

        if ($role !== 'customer') {
            $rules['customer_id'] = 'required|exists:users,id';
        }

        $validated = $request->validate($rules);

        $studio = Studio::findOrFail($validated['studio_id']);

        if ($role === 'provider' && $studio->provider_id !== auth()->id()) {
            abort(403);
        }

        if ($role === 'customer' && !$studio->is_active) {
            abort(403);
        }

        $customerId = $role === 'customer'
            ? auth()->id()
            : $validated['customer_id'];

        Booking::create([
            'reference' => 'SPC-' . strtoupper(Str::random(8)),
            'customer_id' => $customerId,
            'studio_id' => $studio->id,
            'mode' => $validated['mode'],
            'starts_at' => $validated['starts_at'],
            'ends_at' => $validated['ends_at'],
            'total_price' => $validated['total_price'],
            'currency' => 'JOD',
            'status' => 'pending_payment',
        ]);

        return redirect()->route('bookings.index')
            ->with('success', 'Booking created successfully!');
    }

    public function show(Booking $booking)
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $booking = Booking::with(['customer', 'studio'])
                ->findOrFail($booking->id);

            return view('admin.bookings.show', compact('booking'));

        } elseif ($role === 'provider') {

            $booking = Booking::with(['customer', 'studio'])
                ->whereHas('studio', function ($query) {
                    $query->where('provider_id', auth()->id());
                })
                ->findOrFail($booking->id);

            return view('provider.bookings.show', compact('booking'));

        } elseif ($role === 'customer') {

            $booking = Booking::with(['customer', 'studio'])
                ->where('customer_id', auth()->id())
                ->findOrFail($booking->id);

            return view('customer.bookings.show', compact('booking'));
        }

        abort(403);
    }

    public function edit(Booking $booking)
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $booking = Booking::with(['customer', 'studio'])
                ->findOrFail($booking->id);

            $studios = Studio::all();

            return view('admin.bookings.edit', compact('booking', 'studios'));

        } elseif ($role === 'provider') {

            $booking = Booking::with(['customer', 'studio'])
                ->whereHas('studio', function ($query) {
                    $query->where('provider_id', auth()->id());
                })
                ->findOrFail($booking->id);

            $studios = Studio::where('provider_id', auth()->id())->get();

            return view('provider.bookings.edit', compact('booking', 'studios'));

        } elseif ($role === 'customer') {

            $booking = Booking::with(['customer', 'studio'])
                ->where('customer_id', auth()->id())
                ->findOrFail($booking->id);

            return view('customer.bookings.edit', compact('booking'));
        }

        abort(403);
    }

    public function update(Request $request, Booking $booking)
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $booking = Booking::findOrFail($booking->id);

        } elseif ($role === 'provider') {

            $booking = Booking::whereHas('studio', function ($query) {
                $query->where('provider_id', auth()->id());
            })->findOrFail($booking->id);

        } elseif ($role === 'customer') {

            $booking = Booking::where('customer_id', auth()->id())
                ->findOrFail($booking->id);

        } else {
            abort(403);
        }

        $validated = $request->validate([
            'mode' => 'required|in:space,service,package,custom',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $booking->update($validated);

        return redirect()->route('bookings.index')
            ->with('success', 'Booking updated successfully!');
    }

    public function destroy(Booking $booking)
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $booking = Booking::findOrFail($booking->id);

        } elseif ($role === 'provider') {

            $booking = Booking::whereHas('studio', function ($query) {
                $query->where('provider_id', auth()->id());
            })->findOrFail($booking->id);

        } elseif ($role === 'customer') {

            $booking = Booking::where('customer_id', auth()->id())
                ->findOrFail($booking->id);

        } else {
            abort(403);
        }

        $booking->delete();

        return redirect()->route('bookings.index')
            ->with('success', 'Booking deleted successfully!');
    }
}