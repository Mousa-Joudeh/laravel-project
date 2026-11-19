<?php

namespace App\Http\Controllers;
use App\Models\Availability;
use App\Models\Studio;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View

    {
        $user = $this->manager();
        $query = Availability::query()
            ->with(['studio', 'service']);
        if ($user->role === 'provider') {
            $query->whereHas('studio', function ($query) use ($user) {
                $query->where('provider_id', $user->id);
            });
        }
        $availabilities = $query
            ->latest('id')
            ->paginate(15);
        return view(
            'availabilities.index',
            compact('availabilities')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $user = $this->manager();
        $studios = $this->availableStudios($user);
        return view('availabilities.create', compact('studios'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $this->manager();
        $data = $this->validatedData($request, $user);
        Availability::create($data);
        return redirect()->route('availabilities.index')
            ->with('success', 'Availability created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Availability $availability): View
    {
        $user = $this->manager();
        $this->checkOwnership($availability, $user);
        $availability->load(['studio', 'service']);
        return view(
            'availabilities.show',
            compact('availability')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Availability $availability): View
    {
        $user = $this->manager();
        $this->checkOwnership($availability, $user);
        $availability->load(['studio', 'service']);
        $studios = $this->availableStudios($user);
        return view(
            'availabilities.edit',
            compact('availability', 'studios')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request,Availability $availability): RedirectResponse
    {
        $user = $this->manager();

        $this->checkOwnership($availability, $user);

        $data = $this->validatedData($request, $user);
        $availability->update($data);
        return redirect()->route('availabilities.index')
            ->with('success', 'Availability updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Availability $availability): RedirectResponse
    {
        $user = $this->manager();
        $this->checkOwnership($availability, $user);
        // Soft Delete حسب الـ Model الموجود.
        $availability->delete();
        return redirect()->route('availabilities.index')
            ->with('success', 'Availability deleted successfully.');
    }
    private function manager(): User
    {
        $user = auth()->user();
        if (
            !$user
            || !in_array($user->role, ['admin', 'provider'], true)
        ) {
            abort(403);
        }
        return $user;
    }
    private function checkOwnership(
        Availability $availability,
        User $user
    ): void {
        if ($user->role === 'admin') {
            return;
        }
        $ownsStudio = $availability->studio()
            ->where('provider_id', $user->id)
            ->exists();
        if (!$ownsStudio) {
            abort(403);
        }
    }
    private function availableStudios(User $user)
    {
        $query = Studio::query()
            ->with('services:id,studio_id,title');
        if ($user->role === 'provider') {
            $query->where('provider_id', $user->id);
        }
        return $query
            ->orderBy('name')
            ->get(['id', 'name', 'provider_id']);
    }
    private function validatedData(
        Request $request,
        User $user
    ): array {
        $validated = $request->validate([
            'studio_id' => [
                'required',
                'integer',
                Rule::exists('studios', 'id')
                    ->whereNull('deleted_at'),
            ],
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')
                    ->whereNull('deleted_at'),
            ],
            'type' => [
                'required',
                Rule::in(['weekly', 'blocked']),
            ],
            'day_of_week' => [
                'exclude_unless:type,weekly',
                'required',
                'integer',
                'between:0,6',
            ],
            'start_time' => [
                'exclude_unless:type,weekly',
                'required',
                'date_format:H:i',
            ],
            'end_time' => [
                'exclude_unless:type,weekly',
                'required',
                'date_format:H:i',
                'after:start_time',
            ],
            'starts_at' => [
                'exclude_unless:type,blocked',
                'required',
                'date',
            ],
            'ends_at' => [
                'exclude_unless:type,blocked',
                'required',
                'date',
                'after:starts_at',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);
        $studio = Studio::findOrFail($validated['studio_id']);
        if (
            $user->role === 'provider'
            && (int) $studio->provider_id !== (int) $user->id
        ) {
            abort(403);
        }
        $serviceId = $validated['service_id'] ?? null;
        if ($serviceId !== null) {
            $serviceBelongsToStudio = $studio->services()
                ->whereKey($serviceId)
                ->exists();
            if (!$serviceBelongsToStudio) {
                throw ValidationException::withMessages([
                    'service_id' => [
                        'The selected service must belong to the selected studio.',
                    ],
                ]);
            }
        }
        $isWeekly = $validated['type'] === 'weekly';
        return [
            'studio_id' => $studio->id,
            'service_id' => $serviceId,
            'type' => $validated['type'],
            // تنظيف الحقول غير التابعة للنوع المختار.
            'day_of_week' => $isWeekly
                ? (int) $validated['day_of_week']
                : null,
            'start_time' => $isWeekly
                ? $validated['start_time']
                : null,
            'end_time' => $isWeekly
                ? $validated['end_time']
                : null,
            'starts_at' => !$isWeekly
                ? $validated['starts_at']
                : null,
            'ends_at' => !$isWeekly
                ? $validated['ends_at']
                : null,
            'reason' => $validated['reason'] ?? null,
        ];
    }
}