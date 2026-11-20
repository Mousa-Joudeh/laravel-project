<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Booking;
use App\Models\Package;
use Illuminate\Http\Request;


class PackageSlotController extends Controller
{
   
    public function index(Request $request, string $id)
    {
        $request->validate([
            'date' => 'required|date',
            'hours' => 'required|integer|min:1|max:12',
        ]);

        $package = Package::findOrFail($id);
        $hours = (int) $request->hours;
        $day = date('w', strtotime($request->date)); // 0 = Sunday ... 6 = Saturday

        
        $openings = Availability::where('studio_id', $package->studio_id)
            ->where('type', 'weekly')
            ->where('day_of_week', $day)
            ->whereNull('service_id')
            ->get();

        $slots = [];

        foreach ($openings as $opening) {
            $start = strtotime($request->date . ' ' . $opening->start_time);
            $close = strtotime($request->date . ' ' . $opening->end_time);

           
            while ($start + ($hours * 3600) <= $close) {
                $from = date('Y-m-d H:i:s', $start);
                $to = date('Y-m-d H:i:s', $start + ($hours * 3600));

                $isPast = $start <= time() + 3600;

                $isBooked = Booking::where('studio_id', $package->studio_id)
                    ->whereIn('status', ['pending_payment', 'confirmed'])
                    ->where('starts_at', '<', $to)
                    ->where('ends_at', '>', $from)
                    ->exists();

                $isBlocked = Availability::where('studio_id', $package->studio_id)
                    ->where('type', 'blocked')
                    ->where('starts_at', '<', $to)
                    ->where('ends_at', '>', $from)
                    ->exists();

                if (!$isPast && !$isBooked && !$isBlocked) {
                    $slots[] = date('H:i', $start);
                }

                $start = $start + 3600;
            }
        }

        return response()->json(['slots' => $slots]);
    }
}