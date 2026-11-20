@extends('layouts.user')

@section('title', 'Booking confirmed')

@push('styles')
    <link href="{{ asset('css/booking-success.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="container">
        <div class="success-wrap">

            
            <div class="shutter">
                <svg viewBox="0 0 104 104" fill="none">
                    <circle cx="52" cy="52" r="50" fill="#fff1ea"/>
                    <g class="blades">
                        <circle cx="52" cy="52" r="36" fill="#ff5b1a"/>
                        <path d="M52 16 L62 44 M88 52 L60 62 M52 88 L42 60 M16 52 L44 42 M77.5 26.5 L57 47 M26.5 77.5 L47 57" stroke="#fff" stroke-opacity=".35" stroke-width="2"/>
                    </g>
                    <path class="tick" d="M36 53 L47 64 L69 41" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>

            <span class="eyebrow"><b>●</b> Booking confirmed</span>
            <h1>You're <span class="serif">in the frame</span>, {{ explode(' ', Auth::user()->name)[0] }}!</h1>
            <p class="muted">Your booking was sent to {{ $booking->studio->name }}. Show your reference when you arrive.</p>

            {{-- the ticket --}}
            <div class="ticket">
                <div class="ticket-top">
                    <img src="{{ $coverUrl }}" alt="" class="ticket-img">
                    <div>
                        <span class="ticket-ref">{{ $booking->reference }}</span>
                        <h3>{{ $booking->studio->name }}</h3>
                        <p class="muted">{{ ucfirst($booking->mode) }} booking · {{ $booking->studio->city }}</p>
                    </div>
                </div>

                <div class="ticket-cut"></div>

                <div class="ticket-grid">
                    <div><small>Date</small><b>{{ $booking->starts_at->format('D, j M Y') }}</b></div>
                    <div><small>Time</small><b>{{ $booking->starts_at->format('g:i A') }} – {{ $booking->ends_at->format('g:i A') }}</b></div>
                    <div><small>Total</small><b>JD {{ number_format($booking->total_price, 2) }}</b></div>
                </div>
            </div>

            <div class="success-actions">
                <a href="{{ route('my-bookings.index') }}" class="btn btn-primary btn-lg"><i class="bi bi-calendar2-check"></i> My Bookings</a>
                <a href="{{ route('studios.index') }}" class="btn btn-light btn-lg">Keep exploring</a>
            </div>
        </div>
    </div>
@endsection