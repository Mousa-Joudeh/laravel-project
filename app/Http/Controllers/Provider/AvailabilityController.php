<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Provider\Concerns\ScopesToProvider;
use App\Models\Availability;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AvailabilityController extends Controller
{
    use ScopesToProvider;

    /** day_of_week follows PHP/Carbon: 0 = Sunday ... 6 = Saturday. */
    public const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

    private function mine()
    {
        return Availability::whereHas('studio', fn ($q) => $q->where('provider_id', auth()->id()))
            ->with(['studio', 'service']);
    }

    public function index()
    {
        $weekly = $this->mine()
            ->where('type', 'weekly')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        $blocked = $this->mine()
            ->where('type', 'blocked')
            ->where('ends_at', '>=', now()->subDay())
            ->orderBy('starts_at')
            ->get();

        return view('provider.panel.availabilities.index', [
            'days'     => self::DAYS,
            'weekly'   => $weekly,
            'blocked'  => $blocked,
            'studios'  => $this->myStudios()->orderBy('name')->get(['id', 'name']),
            'services' => Service::whereHas('studio', fn ($q) => $q->where('provider_id', auth()->id()))
                ->orderBy('title')->get(['id', 'studio_id', 'title']),
        ]);
    }

    public function store(Request $request)
    {
        $type = $request->validate(['type' => 'required|in:weekly,blocked'])['type'];

        $rules = [
            'target' => 'nullable|string|max:30',
            'reason' => 'nullable|string|max:255',
        ];

        $rules += $type === 'weekly'
            ? [
                'day_of_week'   => 'required|array|min:1',
                'day_of_week.*' => 'integer|between:0,6',
                'start_time'    => 'required|date_format:H:i',
                'end_time'      => 'required|date_format:H:i|after:start_time',
            ]
            : [
                'starts_at' => 'required|date',
                'ends_at'   => 'required|date|after:starts_at',
            ];

        $data = $request->validate($rules);

        $targets = $this->resolveTargets($data['target'] ?? null);

        DB::transaction(function () use ($type, $data, $targets) {
            foreach ($targets as [$studioId, $serviceId]) {
                if ($type === 'weekly') {
                    foreach (array_unique($data['day_of_week']) as $day) {
                        Availability::create([
                            'studio_id'   => $studioId,
                            'service_id'  => $serviceId,
                            'type'        => 'weekly',
                            'day_of_week' => (int) $day,
                            'start_time'  => $data['start_time'],
                            'end_time'    => $data['end_time'],
                        ]);
                    }
                } else {
                    Availability::create([
                        'studio_id'  => $studioId,
                        'service_id' => $serviceId,
                        'type'       => 'blocked',
                        'starts_at'  => $data['starts_at'],
                        'ends_at'    => $data['ends_at'],
                        'reason'     => $data['reason'] ?? null,
                    ]);
                }
            }
        });

        return back()->with('status', $type === 'weekly' ? 'Opening hours added.' : 'Period closed.');
    }

    public function destroy(Availability $availability)
    {
        abort_unless((int) $availability->studio?->provider_id === (int) auth()->id(), 404);

        $availability->delete();

        return back()->with('status', 'Removed.');
    }

    /**
     * "" = every space of this provider, "studio:ID" = one space,
     * "service:ID" = one service (stored together with its space).
     *
     * @return array<int, array{0:int,1:?int}>  [studio_id, service_id] pairs
     */
    private function resolveTargets(?string $target): array
    {
        if (blank($target)) {
            $ids = $this->myStudios()->pluck('id');

            if ($ids->isEmpty()) {
                throw ValidationException::withMessages(['target' => 'Add a space first.']);
            }

            return $ids->map(fn ($id) => [$id, null])->all();
        }

        [$kind, $id] = array_pad(explode(':', $target, 2), 2, null);

        if ($kind === 'studio' && ctype_digit((string) $id)) {
            $studio = $this->myStudios()->whereKey($id)->first();

            if ($studio) {
                return [[$studio->id, null]];
            }
        }

        if ($kind === 'service' && ctype_digit((string) $id)) {
            $service = Service::whereKey($id)
                ->whereHas('studio', fn ($q) => $q->where('provider_id', auth()->id()))
                ->first();

            if ($service) {
                return [[$service->studio_id, $service->id]];
            }
        }

        throw ValidationException::withMessages(['target' => 'Pick one of your own spaces or services.']);
    }
}
