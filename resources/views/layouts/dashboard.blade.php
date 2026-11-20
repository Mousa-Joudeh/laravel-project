<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · SPACES Studio</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect x='2' y='8' width='28' height='20' rx='6' fill='%23141413'/%3E%3Ccircle cx='16' cy='17' r='6.6' fill='%23fff'/%3E%3Ccircle cx='16' cy='17' r='4.4' fill='%23ff5b1a'/%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/spaces.css') }}">
</head>
<body class="dash-body">
    @include('partials.flash')

    <div class="dash">
        <aside class="dash-side" id="dash-side">
            <a href="{{ route('provider.dashboard') }}" class="brand">
                @include('partials.logo')
                <span>SPACES</span>
                <span class="tag tag-dark" style="height:18px;font-size:10px;letter-spacing:.06em">STUDIO</span>
            </a>

            @include('partials.dash-provider-nav')

            <div class="bottom dash-nav">
                <a href="{{ url('/') }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View website</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-ghost btn-block" style="justify-content:flex-start;padding:0 10px;height:38px;border-radius:9px"><i class="bi bi-box-arrow-left" style="color:var(--ink-3)"></i> Logout</button>
                </form>
            </div>
        </aside>

        <div class="dash-main">
            <header class="dash-top">
                <button class="btn btn-icon btn-light dash-toggle" data-toggle="#dash-side" aria-label="Menu"><i class="bi bi-list"></i></button>
                <div class="title">
                    @yield('title', 'Dashboard')
                    <small>{{ now()->format('l, j F Y') }}</small>
                </div>
                <div class="actions">
                    @yield('actions')
                    <div class="dropdown" data-dropdown>
                        <button class="avatar-btn" data-dropdown-btn aria-expanded="false">
                            @if (auth()->user()->avatarUrl())
                                <img src="{{ auth()->user()->avatarUrl() }}" class="avatar" alt="">
                            @elseif (auth()->user()->providerProfile?->logoUrl())
                                <img src="{{ auth()->user()->providerProfile->logoUrl() }}" class="avatar" alt="">
                            @else
                                <span class="avatar">{{ auth()->user()->initials() }}</span>
                            @endif
                        </button>
                        <div class="dropdown-menu">
                            <div class="dropdown-head">
                                <div><b>{{ auth()->user()->name }}</b><small>{{ auth()->user()->email }}</small></div>
                            </div>
                            @if (Route::has('provider.profile.edit'))
                                <a href="{{ route('provider.profile.edit') }}"><i class="bi bi-person-gear"></i> Profile</a>
                            @endif
                            <a href="{{ url('/') }}"><i class="bi bi-globe2"></i> Website</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <div class="dash-content">
                @yield('content')
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="{{ asset('js/spaces.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
