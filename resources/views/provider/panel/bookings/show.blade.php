@extends('layouts.dashboard')

@section('title', 'Booking ' . $booking->reference)

@section('content')
    <div class="dash-head">
        <div>
            <a href="{{ route('provider.bookings.index') }}" class="muted"><i class="bi bi-arrow-left"></i> All bookings</a>
            <h1 style="margin-top:6px">{{ $booking->title() }}</h1>
            <p>{{ $booking->customer->name }} · {{ $booking->starts_at->format('D j M Y, g:i A') }}</p>
        </div>

        @if ($booking->status === 'pending_payment')
            <div class="row">
                <form method="POST" action="{{ route('provider.bookings.update', $booking) }}" data-confirm="Cancel this booking? The customer will see it as cancelled.">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="cancelled">
                    <button class="btn btn-danger btn-sm">Cancel booking</button>
                </form>
                <form method="POST" action="{{ route('provider.bookings.update', $booking) }}" data-confirm="Confirm this booking?">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="confirmed">
                    <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Confirm booking</button>
                </form>
            </div>
        @endif

        @if ($booking->status === 'confirmed')
            <div class="row">
                <form method="POST" action="{{ route('provider.bookings.update', $booking) }}" data-confirm="Cancel this booking? The customer will see it as cancelled.">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="cancelled">
                    <button class="btn btn-danger btn-sm">Cancel booking</button>
                </form>
                <form method="POST" action="{{ route('provider.bookings.update', $booking) }}" data-confirm="Mark this session as completed?">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="completed">
                    <button class="btn btn-success btn-sm"><i class="bi bi-check2-circle"></i> Mark completed</button>
                </form>
            </div>
        @endif
    </div>

    @include('partials.booking-details')
@endsection
