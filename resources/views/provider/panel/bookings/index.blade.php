@extends('layouts.dashboard')

@section('title', 'Booking Management')

@section('content')
    <div class="dash-head">
        <div>
            <h1>Bookings</h1>
            <p>Confirm new bookings, then mark them completed after the session. You can cancel a booking at any time before that.</p>
        </div>
    </div>

    <nav class="tabs">
        <a href="{{ route('provider.bookings.index') }}" @class(['active' => ! request('status') && ! request('when')])>All <span class="count">{{ $counts->sum() }}</span></a>
        <a href="{{ route('provider.bookings.index', ['when' => 'upcoming', 'status' => 'confirmed']) }}" @class(['active' => request('when') === 'upcoming'])>Upcoming</a>
        @foreach (['pending_payment', 'confirmed', 'completed', 'cancelled'] as $status)
            <a href="{{ route('provider.bookings.index', ['status' => $status]) }}" @class(['active' => request('status') === $status && ! request('when')])>{{ ucfirst(str_replace('_', ' ', $status)) }} <span class="count">{{ $counts[$status] ?? 0 }}</span></a>
        @endforeach
    </nav>

    <div class="panel">
        <form class="toolbar" method="GET">
            <input type="hidden" name="status" value="{{ request('status') }}">
            <div class="input-icon">
                <i class="bi bi-search"></i>
                <input type="text" name="q" value="{{ request('q') }}" class="input" placeholder="Reference or customer name">
            </div>
            <button class="btn btn-dark btn-sm">Search</button>
        </form>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Booking</th><th>Customer</th><th>When</th><th>Total</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>
                                <a href="{{ route('provider.bookings.show', $booking) }}" class="cell-main" style="min-width:200px">
                                    <div><b>{{ $booking->title() }}</b><small class="mono">{{ $booking->reference }} · {{ ucfirst($booking->mode) }}</small></div>
                                </a>
                            </td>
                            <td>
                                <div class="row" style="gap:8px">
                                    @if ($booking->customer->avatarUrl())
                                        <img src="{{ $booking->customer->avatarUrl() }}" class="avatar avatar-sm" alt="">
                                    @else
                                        <span class="avatar avatar-sm">{{ $booking->customer->initials() }}</span>
                                    @endif
                                    <div><b style="font-weight:500">{{ $booking->customer->name }}</b><br><small class="muted">{{ $booking->customer->phone }}</small></div>
                                </div>
                            </td>
                            <td class="nowrap">{{ $booking->starts_at->format('D j M Y') }}<br><small class="muted">{{ $booking->starts_at->format('g:i A') }} · {{ $booking->hours() }}h</small></td>
                            <td class="nowrap">JD {{ number_format($booking->total_price, 2) }}</td>
                            <td><x-status :value="$booking->status" /></td>
                            <td>
                                <div class="table-actions">
                                    @if ($booking->status === 'pending_payment')
                                        <form method="POST" action="{{ route('provider.bookings.update', $booking) }}" data-confirm="Confirm booking {{ $booking->reference }}?">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="confirmed">
                                            <button class="icon-btn" title="Confirm booking"><i class="bi bi-check2-circle"></i></button>
                                        </form>
                                        <form method="POST" action="{{ route('provider.bookings.update', $booking) }}" data-confirm="Cancel booking {{ $booking->reference }}?">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="cancelled">
                                            <button class="icon-btn danger" title="Cancel booking"><i class="bi bi-x-circle"></i></button>
                                        </form>
                                    @endif
                                    @if ($booking->status === 'confirmed')
                                        <form method="POST" action="{{ route('provider.bookings.update', $booking) }}" data-confirm="Mark {{ $booking->reference }} as completed?">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="completed">
                                            <button class="icon-btn" title="Mark completed"><i class="bi bi-check2-circle"></i></button>
                                        </form>
                                    @endif
                                    <a href="{{ route('provider.bookings.show', $booking) }}" class="icon-btn" title="Details"><i class="bi bi-eye"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><x-empty icon="bi-calendar2" title="No bookings found" /></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{ $bookings->links('partials.pagination') }}
@endsection
