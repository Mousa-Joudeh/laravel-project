<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Studio;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
            // For mode "space" the price is calculated here, so it is only required for the other modes.
            'total_price' => 'required_unless:mode,space|nullable|numeric|min:0',
            // Optional add-ons: ids of the studio's services that have kind "addon" or "both".
            'addons' => 'nullable|array|max:20',
            'addons.*' => 'integer|distinct',
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

        $startsAt = Carbon::parse($validated['starts_at']);
        $endsAt = Carbon::parse($validated['ends_at']);
        $hours = round($startsAt->diffInMinutes($endsAt) / 60, 2);

        /* ---------- Lines of the booking (prices always come from the database) ---------- */
        $lines = [];

        if ($validated['mode'] === 'space') {
            $lines[] = [
                'item_type' => 'space',
                'service_id' => null,
                'item_name' => $studio->name,
                'pricing_type' => 'hourly',
                'unit_price' => $studio->hourly_price,
                'line_total' => round($studio->hourly_price * $hours, 3),
            ];
        }

        $addonIds = $validated['addons'] ?? [];

        if ($addonIds) {
            $addons = Service::whereIn('id', $addonIds)
                ->whereIn('studio_id', Studio::where('provider_id', $studio->provider_id)->pluck('id'))
                ->where('status', 'approved')
                ->where('is_active', true)
                ->whereIn('kind', ['addon', 'both'])
                ->get();

            if ($addons->count() !== count($addonIds)) {
                throw ValidationException::withMessages([
                    'addons' => 'One or more add-ons are not available for this studio.',
                ]);
            }

            foreach ($addons as $addon) {
                $lines[] = [
                    'item_type' => 'addon',
                    'service_id' => $addon->id,
                    'item_name' => $addon->title,
                    'pricing_type' => $addon->pricing_type,
                    'unit_price' => $addon->price,
                    'line_total' => $addon->pricing_type === 'hourly'
                        ? round($addon->price * $hours, 3)
                        : (float) $addon->price,
                ];
            }
        }

        // Space bookings: sum of the lines. Other modes: the submitted base price plus the add-ons.
        $total = $validated['mode'] === 'space'
            ? collect($lines)->sum('line_total')
            : (float) $validated['total_price'] + collect($lines)->sum('line_total');

            $booking = DB::transaction(function () use ($customerId, $studio, $validated, $total, $lines) {
            $booking = Booking::create([
                'reference' => $this->newReference(),
                'customer_id' => $customerId,
                'studio_id' => $studio->id,
                'mode' => $validated['mode'],
                'starts_at' => $validated['starts_at'],
                'ends_at' => $validated['ends_at'],
                'total_price' => $total,
                'currency' => 'JOD',
                'status' => 'pending_payment',
            ]);

            foreach ($lines as $line) {
                $booking->items()->create($line + ['is_included' => false]);
            }
             return $booking;
        });
        if ($role === 'customer') {
            return redirect()->route('bookings.success', $booking->id);
        }

        return redirect()->route('bookings.index')
            ->with('success', 'Booking created successfully!');
    }

    /** Short readable reference, e.g. BK-7K3M9QXA (the column holds 30 characters, a UUID needs 36). */
    private function newReference(): string
    {
        do {
            $reference = 'BK-' . strtoupper(Str::random(8));
        } while (Booking::withTrashed()->where('reference', $reference)->exists());

        return $reference;
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