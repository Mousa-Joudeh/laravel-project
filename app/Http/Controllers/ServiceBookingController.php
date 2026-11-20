<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Models\Studio;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceBookingController extends Controller
{
    public function store(Request $request, Service $service)
    {
        abort_unless($request->user()?->role === 'customer', 403);

        $validated = $request->validate([
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        abort_unless(
            $service->is_active
                && $service->status === 'approved'
                && in_array($service->kind, ['core', 'both'], true),
            404
        );

        $startsAt = Carbon::parse($validated['starts_at']);
        $endsAt = Carbon::parse($validated['ends_at']);
        $seconds = $endsAt->getTimestamp() - $startsAt->getTimestamp();
        $hours = intdiv($seconds, 3600);

        if (
            $seconds % 3600 !== 0
            || $startsAt->second !== 0
            || $hours < max(1, $service->min_hours)
            || $hours > min(12, $service->max_hours)
        ) {
            throw ValidationException::withMessages([
                'ends_at' => 'Choose a valid duration for this service.',
            ]);
        }

        $total = $service->pricing_type === 'hourly'
            ? round((float) $service->price * $hours, 3)
            : $service->price;

        $booking = DB::transaction(function () use ($request, $service, $startsAt, $endsAt, $hours, $total) {
            $studio = Studio::query()
                ->where('is_active', true)
                ->where('status', 'approved')
                ->lockForUpdate()
                ->findOrFail($service->studio_id);

            $slotRequest = new Request([
                'date' => $startsAt->toDateString(),
                'hours' => $hours,
            ]);

            $availability = app(PackageSlotController::class)
                ->index($slotRequest, (string) $studio->id)
                ->getData(true);

            if (!in_array($startsAt->format('H:i'), $availability['slots'], true)) {
                throw ValidationException::withMessages([
                    'starts_at' => 'This time is no longer available. Please choose another time.',
                ]);
            }

            $booking = Booking::create([
                'reference' => 'SPC-' . strtoupper(Str::random(8)),
                'customer_id' => $request->user()->id,
                'studio_id' => $studio->id,
                'mode' => 'service',
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'total_price' => $total,
                'currency' => 'JOD',
                'status' => 'pending_payment',
            ]);

            $booking->items()->create([
                'item_type' => 'service',
                'service_id' => $service->id,
                'item_name' => $service->title,
                'pricing_type' => $service->pricing_type,
                'unit_price' => $service->price,
                'line_total' => $total,
                'is_included' => false,
            ]);

            return $booking;
        });

        return redirect()->route('bookings.success', $booking->id);
    }
}