@php
    $user = auth()->user();
    $profile = $user->providerProfile;
    $todayCount = $user->providerBookings()->where('bookings.status', 'confirmed')->whereDate('bookings.starts_at', today())->count();

    // Resolve a link to the first route name that exists in your app.
    // Items whose routes aren't defined yet are hidden, so there are no dead links.
    $link = function (array $names) {
        foreach ($names as $n) {
            if (Route::has($n)) return $n;
        }
        return null;
    };

    $catalogue = [
        ['Service Management', 'bi-person-video2',  ['provider.services.index'], 'provider.services.*'],
        ['Space Management',   'bi-house-door',     ['provider.studios.index'], 'provider.studios.*'],
        ['Package Management', 'bi-box2-heart',     ['provider.packages.index'], 'provider.packages.*'],
        ['Opening hours',      'bi-clock-history',  ['provider.availabilities.index'], 'provider.availabilities.*'],
    ];
    $account = [
        ['Reviews & Ratings',  'bi-star',           ['provider.reviews.index'], 'provider.reviews.*'],
        ['Profile Management', 'bi-person-gear',    ['provider.profile.edit'],         'provider.profile.*'],
    ];
    $bookingsRoute = $link(['provider.bookings.index']);
@endphp

@if ($profile)
    <div class="who">
        <span class="logo">
            @if ($profile->logoUrl())
                <img src="{{ $profile->logoUrl() }}" alt="">
            @else
                <span class="avatar" style="width:34px;height:34px;border-radius:10px">{{ $profile->initials() }}</span>
            @endif
        </span>
        <div style="min-width:0">
            <b>{{ $profile->company_name }}</b>
            <x-status :value="$profile->status" style="height:18px;font-size:10.5px;margin-top:2px" />
        </div>
    </div>
@endif

<nav class="dash-nav">
    <a href="{{ route('provider.dashboard') }}" @class(['active' => request()->routeIs('provider.dashboard')])><i class="bi bi-grid-1x2"></i> Dashboard</a>

    @if ($bookingsRoute)
        <a href="{{ route($bookingsRoute) }}" @class(['active' => request()->routeIs('provider.bookings.*')])>
            <i class="bi bi-calendar2-check"></i> Booking Management
            @if ($todayCount) <span class="pill-count" title="Today">{{ $todayCount }}</span> @endif
        </a>
    @endif

    @foreach ([['Catalogue', $catalogue], ['Account', $account]] as [$label, $items])
        @php $visible = collect($items)->filter(fn ($i) => $link($i[2])); @endphp
        @if ($visible->isNotEmpty())
            <div class="dash-nav-label">{{ $label }}</div>
            @foreach ($visible as [$text, $icon, $names, $pattern])
                <a href="{{ route($link($names)) }}" @class(['active' => request()->routeIs($pattern)])><i class="bi {{ $icon }}"></i> {{ $text }}</a>
            @endforeach
        @endif
    @endforeach
</nav>
