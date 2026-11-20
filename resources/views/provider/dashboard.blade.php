@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('actions')
    @if (Route::has('provider.studios.create') || Route::has('studios.create'))
        <a href="{{ route(Route::has('provider.studios.create') ? 'provider.studios.create' : 'studios.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New space</a>
    @endif
@endsection

@section('content')
    @if ($profile && $profile->status === 'pending')
        <div class="notice"><i class="bi bi-hourglass-split"></i><div><b>Your studio is waiting for approval</b>You can prepare your spaces, services and packages now. They go live once the SPACES team approves your account.</div></div>
    @elseif ($profile && $profile->status === 'rejected')
        <div class="notice bad"><i class="bi bi-x-octagon"></i><div><b>Your studio account was not approved</b>{{ $profile->admin_note ?: 'Please update your profile and try again.' }}@if (Route::has('provider.profile.edit')) <a href="{{ route('provider.profile.edit') }}" class="accent">Edit profile</a>@endif</div></div>
    @endif

    @if ($pending['studios'] || $pending['services'])
        <div class="notice info"><i class="bi bi-info-circle"></i><div><b>Waiting for the admin</b>{{ $pending['studios'] }} {{ \Illuminate\Support\Str::plural('space', $pending['studios']) }} and {{ $pending['services'] }} {{ \Illuminate\Support\Str::plural('service', $pending['services']) }} are under review.</div></div>
    @endif

    <div class="dash-head">
        <div>
            <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ auth()->user()->firstName() }}</h1>
            <p>Here is how {{ $profile->company_name ?? 'your studio' }} is doing.</p>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat accent">
            <span class="spark"></span>
            <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
            <span>Total revenue</span>
            <strong>JD {{ number_format($stats['revenue'], 0) }}</strong>
            <small>+ JD {{ number_format($stats['expected'], 0) }} expected from upcoming</small>
        </div>
        <div class="stat">
            <div class="stat-icon"><i class="bi bi-calendar2-check"></i></div>
            <span>Total bookings</span>
            <strong>{{ $stats['bookings'] }}</strong>
            <small class="up">{{ $stats['upcoming'] }} upcoming</small>
        </div>
        <div class="stat">
            <div class="stat-icon"><i class="bi bi-person-video2"></i></div>
            <span>Services offered</span>
            <strong>{{ $stats['services'] }}</strong>
            <small>{{ $stats['spaces'] }} spaces · {{ $stats['packages'] }} packages</small>
        </div>
        <div class="stat">
            <div class="stat-icon"><i class="bi bi-star"></i></div>
            <span>Average rating</span>
            <strong>{{ $stats['rating'] ?: '–' }}</strong>
            <small>{{ $stats['reviews'] }} {{ \Illuminate\Support\Str::plural('review', $stats['reviews']) }}</small>
        </div>
    </div>

    <div class="dash-grid">
        <div class="panel">
            <div class="panel-head">
                <div><h3>Bookings &amp; revenue</h3><p>Last 6 months</p></div>
            </div>
            <div class="panel-body"><div class="chart-box"><canvas id="chart"></canvas></div></div>
        </div>

        <div class="panel">
            <div class="panel-head">
                <h3>Next sessions</h3>
                @if (Route::has('bookings.index') || Route::has('provider.bookings.index'))
                    <a href="{{ route(Route::has('provider.bookings.index') ? 'provider.bookings.index' : 'bookings.index') }}" class="link-more" style="padding:5px 10px">All</a>
                @endif
            </div>
            <div class="panel-body flush">
                @forelse ($upcoming as $booking)
                    @php $showRoute = Route::has('provider.bookings.show') ? 'provider.bookings.show' : (Route::has('bookings.show') ? 'bookings.show' : null); @endphp
                    <{{ $showRoute ? 'a' : 'div' }} @if ($showRoute) href="{{ route($showRoute, $booking) }}" @endif class="list-item">
                        <span class="date-tile"><small>{{ $booking->starts_at->format('M') }}</small><b>{{ $booking->starts_at->format('d') }}</b></span>
                        <div class="grow" style="min-width:0">
                            <b>{{ $booking->title() }}</b>
                            <small>{{ $booking->customer->name }} · {{ $booking->starts_at->format('g:i A') }} · {{ $booking->hours() }}h</small>
                        </div>
                        <span class="price">JD {{ number_format($booking->total_price, 0) }}</span>
                    </{{ $showRoute ? 'a' : 'div' }}>
                @empty
                    <div class="panel-body"><x-empty icon="bi-calendar2" title="No upcoming sessions" /></div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="panel" style="margin-top:14px">
        <div class="panel-head">
            <h3>Latest reviews</h3>
            @if (Route::has('provider.reviews.index') || Route::has('reviews.index'))
                <a href="{{ route(Route::has('provider.reviews.index') ? 'provider.reviews.index' : 'reviews.index') }}" class="link-more" style="padding:5px 10px">All reviews</a>
            @endif
        </div>
        <div class="panel-body">
            @if ($latestReviews->isNotEmpty())
                <div class="reviews">
                    @foreach ($latestReviews as $review)
                        <article class="review">
                            <div class="head">
                                @if ($review->booking->customer->avatarUrl())
                                    <img src="{{ $review->booking->customer->avatarUrl() }}" class="avatar avatar-sm" alt="">
                                @else
                                    <span class="avatar avatar-sm">{{ $review->booking->customer->initials() }}</span>
                                @endif
                                <div><b style="font-size:13px">{{ $review->booking->customer->name }}</b><small>{{ $review->booking->title() }}</small></div>
                                <x-stars :value="$review->rating" />
                            </div>
                            @if ($review->comment)<p>{{ $review->comment }}</p>@endif
                        </article>
                    @endforeach
                </div>
            @else
                <x-empty icon="bi-chat-heart" title="No reviews yet" />
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            Chart.defaults.font.family = 'Inter, sans-serif';
            Chart.defaults.font.size = 11;
            Chart.defaults.color = '#8f8c86';
            new Chart(document.getElementById('chart'), {
                data: {
                    labels: @json($chart['labels']),
                    datasets: [
                        { type: 'bar', label: 'Bookings', data: @json($chart['bookings']), backgroundColor: '#efede9', hoverBackgroundColor: '#141413', borderRadius: 6, yAxisID: 'y', maxBarThickness: 34 },
                        { type: 'line', label: 'Revenue (JD)', data: @json($chart['revenue']), borderColor: '#ff5b1a', backgroundColor: 'rgba(255,91,26,.08)', fill: true, cubicInterpolationMode: 'monotone', pointRadius: 3, pointBackgroundColor: '#ff5b1a', yAxisID: 'y1' },
                    ],
                },
                options: {
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true } } },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, grid: { color: '#f1efec' }, ticks: { precision: 0 } },
                        y1: { beginAtZero: true, position: 'right', grid: { display: false } },
                    },
                },
            });
        });
    </script>
@endpush
