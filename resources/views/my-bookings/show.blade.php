@extends('layouts.user')

@section('title', 'Booking ' . $booking->reference)

@push('styles')
    <link href="{{ asset('css/bookings.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="container container-sm">
        <div class="page-head">
            <nav class="crumbs">
                <a href="{{ route('my-bookings.index') }}">My bookings</a>
                <i class="bi bi-chevron-right"></i>
                <span>{{ $booking->reference }}</span>
            </nav>
            <div class="details-head">
                <h1>{{ $booking->title() }}</h1>
                <span class="badge dot badge-{{ $booking->status }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>
            </div>
        </div>

      
        <div class="ticket">
            <div class="ticket-top">
                <img src="{{ $coverUrl }}" alt="" class="ticket-img">
                <div>
                    <span class="ref">{{ $booking->reference }}</span>
                    <h3>{{ $booking->title() }}</h3>
                    <p class="muted">{{ $booking->studio->provider->providerProfile->company_name ?? $booking->studio->name }}</p>
                </div>
            </div>
            <div class="ticket-cut"></div>
            <div class="ticket-grid">
                <div><small>Date</small><b>{{ $booking->starts_at->format('D, j M Y') }}</b></div>
                <div><small>Time</small><b>{{ $booking->starts_at->format('g:i A') }} – {{ $booking->ends_at->format('g:i A') }}</b></div>
                <div><small>Duration</small><b>{{ $booking->hours() }} hours</b></div>
                <div><small>Total</small><b>JD {{ number_format($booking->total_price, 2) }}</b></div>
                <div><small>Payment</small><b>{{ ucfirst($booking->transactions->first()->status ?? 'pending') }} · at the studio</b></div>
                <div><small>Booked on</small><b>{{ $booking->created_at->format('j M Y') }}</b></div>
            </div>
        </div>

        
        <div class="box">
            <div class="box-head"><h3>What you booked</h3></div>
            <table class="items-table">
                <thead>
                    <tr><th>Item</th><th>Type</th><th>Price</th><th class="right">Total</th></tr>
                </thead>
                <tbody>
                    @forelse ($booking->items as $item)
                        <tr>
                            <td><b>{{ $item->item_name }}</b></td>
                            <td><span class="tag">{{ ucfirst($item->item_type) }}</span></td>
                            <td class="muted">JD {{ number_format($item->unit_price, 2) }} {{ $item->pricing_type == 'hourly' ? '/ hour' : '' }}</td>
                            <td class="right">{{ $item->is_included ? 'Included' : 'JD ' . number_format($item->line_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td><b>{{ $booking->studio->name }}</b></td>
                            <td><span class="tag">{{ ucfirst($booking->mode) }}</span></td>
                            <td class="muted">–</td>
                            <td class="right">JD {{ number_format($booking->total_price, 2) }}</td>
                        </tr>
                    @endforelse
                    <tr>
                        <td colspan="3"><b>Total</b></td>
                        <td class="right"><b>JD {{ number_format($booking->total_price, 2) }}</b></td>
                    </tr>
                </tbody>
            </table>
        </div>

       
        @if ($booking->can_rate || $booking->review)
            <div class="box review-box" id="review">
                <h3>{{ $booking->review ? 'Your review' : 'Rate this session' }}</h3>

                <form method="POST" action="{{ route('my-bookings.review', $booking->id) }}">
                    @csrf
                    <div class="stars-input">
                        @for ($i = 5; $i >= 1; $i--)
                            <input type="radio" name="rating" id="r{{ $i }}" value="{{ $i }}" {{ old('rating', $booking->review->rating ?? '') == $i ? 'checked' : '' }} required>
                            <label for="r{{ $i }}"><i class="bi bi-star-fill"></i></label>
                        @endfor
                    </div>
                    @error('rating') <p class="form-error">{{ $message }}</p> @enderror

                    <textarea name="comment" class="textarea" maxlength="1000" placeholder="What went well? What could be better?">{{ old('comment', $booking->review->comment ?? '') }}</textarea>
                    @error('comment') <p class="form-error">{{ $message }}</p> @enderror

                    <div class="review-foot">
                        <button class="btn btn-dark">{{ $booking->review ? 'Update review' : 'Post review' }}</button>
                    </div>
                </form>
            </div>
        @endif

        <div class="details-foot">
            <a href="{{ route('my-bookings.index', ['tab' => $booking->can_rate ? 'past' : 'upcoming']) }}" class="btn btn-light"><i class="bi bi-arrow-left"></i> All bookings</a>

            @if ($booking->can_cancel)
                <form method="POST" action="{{ route('my-bookings.cancel', $booking->id) }}" onsubmit="return confirm('Cancel this booking? This can not be undone.')">
                    @csrf
                    @method('PATCH')
                    <button class="btn btn-danger">Cancel booking</button>
                </form>
            @endif
        </div>
    </div>
@endsection