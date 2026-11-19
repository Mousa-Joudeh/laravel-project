<aside class="admin-sidebar" id="admin-sidebar">
    <div class="admin-brand">
        <span class="admin-brand-mark" aria-hidden="true"><span></span></span>
        <span class="admin-brand-word">SPACE</span>
        <span class="admin-brand-tag">ADMIN</span>
    </div>

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

        <span class="admin-nav-label">Management</span>
        <a
            href="{{ route('admin.users.index') }}"
            class="admin-nav-link {{ request()->routeIs('admin.users.*', 'users.*') ? 'active' : '' }}"
        >
            <i class="bi bi-people"></i>
            <span>Users</span>
        </a>
        <span class="admin-nav-link disabled"><i class="bi bi-building"></i><span>Providers</span><small>soon</small></span>
        <span class="admin-nav-link disabled"><i class="bi bi-camera"></i><span>Studios</span><small>soon</small></span>
        <span class="admin-nav-link disabled"><i class="bi bi-layers"></i><span>Services & packages</span><small>soon</small></span>
        <span class="admin-nav-link disabled"><i class="bi bi-calendar-check"></i><span>Bookings</span><small>soon</small></span>
        <span class="admin-nav-link disabled"><i class="bi bi-star"></i><span>Reviews</span><small>soon</small></span>

        <span class="admin-nav-label">Moderation</span>
        <span class="admin-nav-link disabled"><i class="bi bi-patch-check"></i><span>Approval requests</span><small>soon</small></span>
    </nav>

    <div class="admin-sidebar-footer">
        <a href="{{ url('/') }}" class="admin-nav-link"><i class="bi bi-box-arrow-up-right"></i><span>View website</span></a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="admin-nav-link admin-logout"><i class="bi bi-box-arrow-right"></i><span>Sign out</span></button>
        </form>
    </div>
</aside>
