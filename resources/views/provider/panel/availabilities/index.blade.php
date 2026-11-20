@extends('layouts.dashboard')

@section('title', 'Opening hours')

@section('content')
    <div class="dash-head">
        <div>
            <h1>Opening hours</h1>
            <p>Customers only see free times inside these hours. Closed periods always win.</p>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h3>Weekly hours</h3><p>Hours for all your resources, or for one space / service</p></div>
        <div class="panel-body">
            <div class="week-grid">
                @foreach ($days as $index => $day)
                    <div class="week-day">
                        <h5>{{ $day }}</h5>
                        @forelse ($weekly[$index] ?? [] as $slot)
                            <div class="week-slot">
                                <div>
                                    {{ substr($slot->start_time, 0, 5) }} – {{ substr($slot->end_time, 0, 5) }}
                                    <small>{{ $slot->targetLabel() }}</small>
                                </div>
                                <form method="POST" action="{{ route('provider.availabilities.destroy', $slot) }}" data-confirm="Remove these hours?">
                                    @csrf
                                    @method('DELETE')
                                    <button title="Remove"><i class="bi bi-x-lg"></i></button>
                                </form>
                            </div>
                        @empty
                            <span class="muted" style="font-size:12px">Closed</span>
                        @endforelse
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="dash-grid">
        <form method="POST" action="{{ route('provider.availabilities.store') }}" class="panel">
            @csrf
            <input type="hidden" name="type" value="weekly">
            <div class="panel-head"><h3>Add weekly hours</h3></div>
            <div class="panel-body stack">
                <div class="field">
                    <span class="label">Days</span>
                    <div class="chip-group">
                        @foreach ($days as $index => $day)
                            <label class="chip"><input type="checkbox" name="day_of_week[]" value="{{ $index }}"><span>{{ substr($day, 0, 3) }}</span></label>
                        @endforeach
                    </div>
                    @error('day_of_week') <p class="error">{{ $message }}</p> @enderror
                </div>
                <div class="form-grid">
                    <div class="field">
                        <label class="label">Opens</label>
                        <input type="time" name="start_time" value="09:00" class="input" required>
                    </div>
                    <div class="field">
                        <label class="label">Closes</label>
                        <input type="time" name="end_time" value="21:00" class="input" required>
                        @error('end_time') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="field">
                    <label class="label">For</label>
                    <select name="target" class="select">
                        <option value="">All my resources</option>
                        @foreach ($studios as $studio)
                            <option value="studio:{{ $studio->id }}">Space: {{ $studio->name }}</option>
                        @endforeach
                        @foreach ($services as $service)
                            <option value="service:{{ $service->id }}">Service: {{ $service->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div><button class="btn btn-dark">Add hours</button></div>
            </div>
        </form>

        <form method="POST" action="{{ route('provider.availabilities.store') }}" class="panel">
            @csrf
            <input type="hidden" name="type" value="blocked">
            <div class="panel-head"><h3>Close a period</h3></div>
            <div class="panel-body stack">
                <div class="form-grid">
                    <div class="field">
                        <label class="label">From</label>
                        <input type="datetime-local" name="starts_at" class="input" required>
                    </div>
                    <div class="field">
                        <label class="label">To</label>
                        <input type="datetime-local" name="ends_at" class="input" required>
                        @error('ends_at') <p class="error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div class="field">
                    <label class="label">For</label>
                    <select name="target" class="select">
                        <option value="">All my resources</option>
                        @foreach ($studios as $studio)
                            <option value="studio:{{ $studio->id }}">Space: {{ $studio->name }}</option>
                        @endforeach
                        @foreach ($services as $service)
                            <option value="service:{{ $service->id }}">Service: {{ $service->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="label">Reason <span class="opt">optional</span></label>
                    <input name="reason" class="input" placeholder="Holiday, maintenance…">
                </div>
                <div><button class="btn btn-dark">Close period</button></div>
            </div>
        </form>
    </div>

    <div class="panel" style="margin-top:14px">
        <div class="panel-head"><h3>Closed periods</h3></div>
        <div class="panel-body flush">
            @forelse ($blocked as $block)
                <div class="list-item">
                    <i class="bi bi-slash-circle" style="color:var(--bad)"></i>
                    <div class="grow">
                        <b>{{ $block->starts_at->format('D j M, H:i') }} → {{ $block->ends_at->format('D j M, H:i') }}</b>
                        <small>{{ $block->targetLabel() }} · {{ $block->reason ?: 'No reason' }}</small>
                    </div>
                    <form method="POST" action="{{ route('provider.availabilities.destroy', $block) }}" data-confirm="Re-open this period?">
                        @csrf
                        @method('DELETE')
                        <button class="icon-btn danger" title="Remove"><i class="bi bi-trash3"></i></button>
                    </form>
                </div>
            @empty
                <div class="panel-body muted">No closed periods.</div>
            @endforelse
        </div>
    </div>
@endsection
