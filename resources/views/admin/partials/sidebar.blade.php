<aside class="admin-sidebar" id="admin-sidebar">
    <a class="admin-brand" href="{{ route('admin.dashboard') }}" aria-label="SPACES Administration Dashboard">
        @include('partials.logo')
        <span class="admin-brand-word">SPACES</span>
        <span class="admin-brand-tag">ADMIN</span>
    </a>

    <div class="admin-account">
        <span class="admin-account-avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
        <span class="admin-account-text">
            <strong>{{ auth()->user()->name }}</strong>
            <small>Platform administrator</small>
        </span>
    </div>

    <nav class="admin-navigation" aria-label="Admin navigation">
        <span class="admin-nav-label">Overview</span>
        <a class="admin-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}" aria-current="{{ request()->routeIs('admin.dashboard') ? 'page' : 'false' }}">
            <i class="bi bi-grid-1x2"></i><span>Dashboard</span>
        </a>

        <a class="admin-nav-link {{ request()->routeIs('admin.analytics.*') ? 'active' : '' }}" href="{{ route('admin.analytics.index') }}">
            <i class="bi bi-graph-up-arrow"></i><span>Analytics & Traffic</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.ai.*') ? 'active' : '' }}" href="{{ route('admin.ai.index') }}">
            <i class="bi bi-stars"></i><span>AI Intelligence</span>
        </a>

        <span class="admin-nav-label">Management</span>
        <a
            href="{{ route('admin.users.index') }}"
            class="admin-nav-link {{ request()->routeIs('admin.users.*', 'users.*') ? 'active' : '' }}"
        >
            <i class="bi bi-people"></i>
            <span>Users</span>
        </a>
        <a class="admin-nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}" href="{{ route('admin.categories.index') }}"><i class="bi bi-tags"></i><span>Categories</span></a>
        <a class="admin-nav-link {{ request()->routeIs('admin.partners.*') ? 'active' : '' }}" href="{{ route('admin.partners.index') }}"><i class="bi bi-buildings"></i><span>Partners</span></a>
        <a class="admin-nav-link {{ request()->routeIs('admin.catalog.*') ? 'active' : '' }}" href="{{ route('admin.catalog.index') }}"><i class="bi bi-camera"></i><span>Studios & Services</span></a>

        <span class="admin-nav-label">Operations</span>
        <a href="{{ route('admin.operations.bookings') }}" class="admin-nav-link {{ request()->routeIs('admin.operations.bookings') ? 'active' : '' }}"><i class="bi bi-calendar-check"></i><span>Bookings</span></a>
        <a href="{{ route('admin.operations.transactions') }}" class="admin-nav-link {{ request()->routeIs('admin.operations.transactions') ? 'active' : '' }}"><i class="bi bi-credit-card"></i><span>Transactions</span></a>
        <a href="{{ route('admin.operations.reviews') }}" class="admin-nav-link {{ request()->routeIs('admin.operations.reviews*') ? 'active' : '' }}"><i class="bi bi-star"></i><span>Reviews</span></a>
        <span class="admin-nav-label">Moderation</span>
        <a class="admin-nav-link {{ request()->routeIs('admin.approvals.*') ? 'active' : '' }}" href="{{ route('admin.approvals.index') }}"><i class="bi bi-patch-check"></i><span>Approval Requests</span></a>
    </nav>

    <div class="admin-sidebar-footer">
        <a href="{{ url('/') }}" class="admin-nav-link"><i class="bi bi-box-arrow-up-right"></i><span>View website</span></a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="admin-nav-link admin-logout"><i class="bi bi-box-arrow-right"></i><span>Sign out</span></button>
        </form>
    </div>
</aside>
