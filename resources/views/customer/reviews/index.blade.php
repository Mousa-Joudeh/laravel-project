@extends('layouts.user')

@section('title', 'My reviews')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/studios.css') }}">
@endpush

@section('content')
    <section class="container" aria-labelledby="my-reviews-title">
        <header class="page-head">
            <h1 id="my-reviews-title">My reviews</h1>
        </header>

        @if ($reviews->isEmpty())
            <div class="empty">
                <p>You haven’t written any reviews yet.</p>
                <a href="{{ route('my-bookings.index') }}" class="btn btn-light">
                    View my bookings
                </a>
            </div>
        @else
            <div class="reviews">
                @foreach ($reviews as $review)
                    <article class="review">
                        <div class="review-head">
                            <div>
                                <strong>{{ $review->booking?->studio?->name ?? 'Studio' }}</strong>
                                <small>{{ $review->created_at?->format('M Y') }}</small>
                            </div>

                            <span class="stars" role="img"
                                  aria-label="{{ $review->rating }} out of 5 stars">
                                @for ($star = 1; $star <= $review->rating; $star++)
                                    <i class="bi bi-star-fill" aria-hidden="true"></i>
                                @endfor
                            </span>
                        </div>

                        @if (filled($review->comment))
                            <p>{{ $review->comment }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        @if ($reviews->hasPages())
            {{ $reviews->links() }}
        @endif
    </section>
@endsection