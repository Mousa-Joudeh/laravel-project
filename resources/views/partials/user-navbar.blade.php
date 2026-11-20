<header class="site-nav">
    <div class="container nav-inner">

        {{-- left: camera logo + SPACES + the name of the logged-in user --}}
        <a href="{{ url('/') }}" class="brand">
            <svg class="brand-mark" viewBox="0 0 32 32" fill="none">
                <rect x="2" y="8" width="28" height="20" rx="6" fill="#141413"/>
                <path d="M10 8l2.2-3.2A2 2 0 0 1 13.8 4h4.4a2 2 0 0 1 1.6.8L22 8" fill="#141413"/>
                <circle cx="16" cy="17" r="6.6" fill="#fff"/>
                <circle cx="16" cy="17" r="4.4" fill="#ff5b1a"/>
                <circle cx="25.6" cy="12" r="1.3" fill="#ff5b1a"/>
            </svg>
            <span>SPACES</span>
        </a>

        @auth
            <span class="nav-sep"></span>
            <span class="nav-hello">
                <span class="dot"></span>
                Hi, <b>{{ explode(' ', Auth::user()->name)[0] }}</b>
            </span>
        @endauth

        {{-- right: links + account --}}
        <div class="nav-right">
            <nav class="nav-links">
                <a href="{{ url('/') }}" class="{{ request()->is('/') ? 'active' : '' }}">Home</a>

                @if (Route::has('studio-companies.index'))
                    <a href="{{ route('studio-companies.index') }}" class="{{ request()->routeIs('studio-companies.*') ? 'active' : '' }}">Studios</a>
                @endif

                <a href="{{ route('studios.index') }}" class="{{ request()->routeIs('studios.*') ? 'active' : '' }}">Spaces</a>
                <a href="{{ route('bookings.index') }}" class="{{ request()->routeIs('bookings.*') ? 'active' : '' }}">My Bookings</a>
            </nav>

            {{-- not logged in --}}
            @guest
                <div class="nav-auth">
                    <a href="{{ route('login') }}" class="btn btn-ghost btn-sm">Login</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-dark btn-sm">Sign Up</a>
                    @endif
                </div>
            @endguest

            {{-- logged in: avatar + menu --}}
            @auth
                <div class="dropdown">
                    <button type="button" class="avatar-btn" id="user-menu-button">
                        @if (Auth::user()->avatar)
                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" class="avatar" alt="">
                        @else
                            <span class="avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                        @endif
                    </button>

                    <div class="dropdown-menu" id="user-menu">
                        <div class="dropdown-head">
                            <b>{{ Auth::user()->name }}</b>
                            <small>{{ Auth::user()->email }}</small>
                        </div>

                        @if (Auth::user()->role === 'customer')
                            @if (Route::has('account.show'))
                                <a href="{{ route('account.show') }}"><i class="bi bi-person"></i> My Profile</a>
                            @endif
                            @if (Route::has('favorites.index'))
                                <a href="{{ route('favorites.index') }}"><i class="bi bi-heart"></i> Favorites</a>
                            @endif
                            <a href="{{ route('bookings.index') }}"><i class="bi bi-calendar2-check"></i> My Bookings</a>
                        @endif

                        @if (Auth::user()->role === 'provider' && Route::has('provider.dashboard'))
                            <a href="{{ route('provider.dashboard') }}"><i class="bi bi-grid-1x2"></i> My Dashboard</a>
                        @endif

                        @if (Auth::user()->role === 'admin' && Route::has('admin.dashboard'))
                            <a href="{{ route('admin.dashboard') }}"><i class="bi bi-grid-1x2"></i> Admin Dashboard</a>
                        @endif

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="danger"><i class="bi bi-box-arrow-right"></i> Logout</button>
                        </form>
                    </div>
                </div>
            @endauth

            <button type="button" class="nav-toggle" id="mobile-menu-button"><i class="bi bi-list"></i></button>
        </div>
    </div>

    {{-- mobile menu --}}
    <div class="nav-drawer" id="mobile-menu">
        <a href="{{ url('/') }}">Home</a>
        @if (Route::has('studio-companies.index'))
            <a href="{{ route('studio-companies.index') }}">Studios</a>
        @endif
        <a href="{{ route('studios.index') }}">Spaces</a>
        <a href="{{ route('bookings.index') }}">My Bookings</a>
        @guest
            <a href="{{ route('login') }}">Login</a>
            <a href="{{ route('register') }}">Sign Up</a>
        @endguest
    </div>
</header>