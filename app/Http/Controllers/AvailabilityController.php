<?php

namespace App\Http\Controllers;

use App\Models\Availability;
use App\Models\Service;
use App\Models\Studio;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{

    private function scopedQuery()
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {
            return Availability::query();
        }

        if ($role === 'provider') {
            return Availability::whereHas('studio', function ($query) {
                $query->where('provider_id', auth()->id());
            });
        }

        abort(403);
    }

    private function validateData(Request $request)
    {
        $rules = [
            'studio_id' => 'required|exists:studios,id',
            'service_id' => 'nullable|exists:services,id',
            'type' => 'required|in:weekly,blocked',
            'reason' => 'nullable|string|max:255',
        ];

        if ($request->type === 'weekly') {
            $rules['day_of_week'] = 'required|integer|between:0,6';
            $rules['start_time'] = 'required|date_format:H:i';
            $rules['end_time'] = 'required|date_format:H:i|after:start_time';
        } else {
            $rules['starts_at'] = 'required|date';
            $rules['ends_at'] = 'required|date|after:starts_at';
        }

        $validated = $request->validate($rules);

        if ($validated['type'] === 'weekly') {
            $validated['starts_at'] = null;
            $validated['ends_at'] = null;
        } else {
            $validated['day_of_week'] = null;
            $validated['start_time'] = null;
            $validated['end_time'] = null;
        }

        return $validated;
    }

    public function index()
    {
        $role = auth()->user()->role;

        $availabilities = $this->scopedQuery()
            ->with(['studio', 'service'])
            ->latest()
            ->paginate(15);

        if ($role === 'admin') {
            return view('admin.availabilities.index', compact('availabilities'));
        }

        return view('provider.availabilities.index', compact('availabilities'));
    }

    public function create()
    {
        $role = auth()->user()->role;

        if ($role === 'admin') {

            $studios = Studio::all();
            $services = Service::all();

            return view('admin.availabilities.create', compact('studios', 'services'));

        } elseif ($role === 'provider') {

            $studios = Studio::where('provider_id', auth()->id())->get();
            $services = Service::whereHas('studio', function ($query) {
                $query->where('provider_id', auth()->id());
            })->get();

            return view('provider.availabilities.create', compact('studios', 'services'));
        }

        abort(403);
    }

    public function store(Request $request)
    {
        $role = auth()->user()->role;

        if ($role !== 'admin' && $role !== 'provider') {
            abort(403);
        }

        $validated = $this->validateData($request);

        $studio = Studio::findOrFail($validated['studio_id']);

        if ($role === 'provider' && $studio->provider_id !== auth()->id()) {
            abort(403);
        }

        Availability::create($validated);

        return redirect()->route('availabilities.index')
            ->with('success', 'Availability created successfully!');
    }

    public function show(Availability $availability)
    {
        $role = auth()->user()->role;

        $availability = $this->scopedQuery()
            ->with(['studio', 'service'])
            ->findOrFail($availability->id);

        if ($role === 'admin') {
            return view('admin.availabilities.show', compact('availability'));
        }

        return view('provider.availabilities.show', compact('availability'));
    }

    public function edit(Availability $availability)
    {
        $role = auth()->user()->role;

        $availability = $this->scopedQuery()
            ->with(['studio', 'service'])
            ->findOrFail($availability->id);

        if ($role === 'admin') {

            $studios = Studio::all();
            $services = Service::all();

            return view('admin.availabilities.edit', compact('availability', 'studios', 'services'));
        }

        $studios = Studio::where('provider_id', auth()->id())->get();
        $services = Service::whereHas('studio', function ($query) {
            $query->where('provider_id', auth()->id());
        })->get();

        return view('provider.availabilities.edit', compact('availability', 'studios', 'services'));
    }

    public function update(Request $request, Availability $availability)
    {
        $role = auth()->user()->role;

        $availability = $this->scopedQuery()->findOrFail($availability->id);

        $validated = $this->validateData($request);

        if ($role === 'provider') {
            $studio = Studio::findOrFail($validated['studio_id']);

            if ($studio->provider_id !== auth()->id()) {
                abort(403);
            }
        }

        $availability->update($validated);

        return redirect()->route('availabilities.index')
            ->with('success', 'Availability updated successfully!');
    }

    public function destroy(Availability $availability)
    {
        $availability = $this->scopedQuery()->findOrFail($availability->id);

        $availability->delete();

        return redirect()->route('availabilities.index')
            ->with('success', 'Availability deleted successfully!');
    }
}