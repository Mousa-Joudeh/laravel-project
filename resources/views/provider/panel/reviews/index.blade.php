@extends('layouts.dashboard')

@section('title', 'Reviews & Ratings')

@section('content')
    <div class="dash-head">
        <div>
            <h1>Reviews & ratings</h1>
            <p>What your customers said after their sessions.</p>
        </div>
    </div>

    <div class="dash-grid" style="grid-template-columns:320px 1fr;margin-top:0">
        <div class="panel">
            <div class="panel-body">
                <div class="rating-big">{{ $summary['count'] ? $summary['avg'] : '–' }}</div>
                <x-stars :value="$summary['avg']" class="accent" style="display:block;margin-top:6px" />
                <p class="muted" style="margin-top:4px">{{ $summary['count'] }} reviews</p>

                <ul class="bar-list" style="margin-top:16px">
                    @for ($i = 5; $i >= 1; $i--)
                        @php $n = $summary['stars'][$i] ?? 0; @endphp
                        <li>
                            <a href="{{ route('provider.reviews.index', ['rating' => $i]) }}">{{ $i }} stars</a>
                            <span class="muted">{{ $n }}</span>
                            <span class="bar"><span style="width:{{ $summary['count'] ? round($n / $summary['count'] * 100) : 0 }}%"></span></span>
                        </li>
                    @endfor
                </ul>
                @if (request('rating'))
                    <a href="{{ route('provider.reviews.index') }}" class="btn btn-light btn-sm" style="margin-top:10px">Show all</a>
                @endif
            </div>
        </div>

        <div class="panel">
            <div class="panel-body flush">
                @forelse ($reviews as $review)
                    <div class="list-item" style="align-items:flex-start">
                        @if ($review->booking->customer->avatarUrl())
                            <img src="{{ $review->booking->customer->avatarUrl() }}" class="avatar" alt="">
                        @else
                            <span class="avatar">{{ $review->booking->customer->initials() }}</span>
                        @endif
                        <div class="grow">
                            <div class="row-between">
                                <b>{{ $review->booking->customer->name }}</b>
                                <x-stars :value="$review->rating" class="accent" />
                            </div>
                            <small>{{ $review->booking->title() }} · {{ $review->created_at->format('j M Y') }} · <a href="{{ route('provider.bookings.show', $review->booking) }}" class="accent">{{ $review->booking->reference }}</a></small>
                            <p style="margin-top:6px;color:var(--ink-2)">{{ $review->comment }}</p>
                        </div>
                    </div>
                @empty
                    <div class="panel-body"><x-empty icon="bi-chat-heart" title="No reviews yet" /></div>
                @endforelse
            </div>
        </div>
    </div>

    {{ $reviews->links('partials.pagination') }}
@endsection
