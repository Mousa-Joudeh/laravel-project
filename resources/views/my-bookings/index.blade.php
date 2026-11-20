@extends('layouts.user')

@section('title', 'My bookings')

@push('styles')
    <link href="{{ asset('css/bookings.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="container container-sm">
        <div class="page-head">
            <h1>My <span class="serif">bookings</span></h1>
            <p>Your sessions: coming up and done.</p>
        </div>

        {{-- tabs --}}
        <nav class="tabs">
            <a href="{{ route('my-bookings.index', ['tab' => 'upcoming']) }}" class="{{ $tab == 'upcoming' ? 'active' : '' }}">
                Upcoming <span class="count">{{ count($upcoming) }}</span>
            </a>
            <a href="{{ route('my-bookings.index', ['tab' => 'past']) }}" class="{{ $tab == 'past' ? 'active' : '' }}">
                Previous <span class="count">{{ count($past) }}</span>
            </a>
        </nav>

        @forelse ($list as $booking)
            <article class="booking-row">
                <a href="{{ route('my-bookings.show', $booking->id) }}" class="booking-img">
                    <img src="{{ $booking->cover_url }}" alt="{{ $booking->title() }}">
                </a>

                <div class="booking-info">
                    <div class="booking-tags">
                        <span class="badge dot badge-{{ $booking->status }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
                        <span class="ref">{{ $booking->reference }}</span>
                    </div>
                    <h4><a href="{{ route('my-bookings.show', $booking->id) }}">{{ $booking->title() }}</a></h4>
                    <div class="when">
                        <span><i class="bi bi-calendar-event"></i> {{ $booking->starts_at->format('D, j M Y') }}</span>
                        <span><i class="bi bi-clock"></i> {{ $booking->starts_at->format('g:i A') }} – {{ $booking->ends_at->format('g:i A') }}</span>
                        <span><i class="bi bi-building"></i> {{ $booking->studio->provider->providerProfile->company_name ?? $booking->studio->name }}</span>
                    </div>
                </div>

                <div class="booking-side">
                    <span class="price">JD {{ number_format($booking->total_price, 2) }}</span>
                    <div class="booking-actions">
                        <a href="{{ route('my-bookings.show', $booking->id) }}" class="btn btn-light btn-sm">Details</a>

                        @if ($booking->can_cancel)
                            <form method="POST" action="{{ route('my-bookings.cancel', $booking->id) }}" onsubmit="return confirm('Cancel booking {{ $booking->reference }}?')">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-danger btn-sm">Cancel</button>
                            </form>
                        @endif

                        @if ($booking->review)
                            <span class="rating"><i class="bi bi-star-fill"></i> {{ $booking->review->rating }}</span>
                        @elseif ($booking->can_rate)
                            <a href="{{ route('my-bookings.show', $booking->id) }}#review" class="btn btn-primary btn-sm"><i class="bi bi-star"></i> Rate</a>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="empty">
                <i class="bi bi-calendar2-x"></i>
                <h4>{{ $tab == 'past' ? 'No previous bookings' : 'No upcoming bookings' }}</h4>
                <p>Find a space or a package and book your next session.</p>
                <a href="{{ route('studios.index') }}" class="btn btn-primary btn-sm">Explore spaces</a>
            </div>
        @endforelse
    </div>
@endsection