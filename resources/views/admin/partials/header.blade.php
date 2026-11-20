<header class="admin-topbar admin-header" aria-label="Administration header">
    <div class="admin-topbar-left admin-header-left">
        <button type="button" class="admin-menu-button" id="admin-menu-button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open navigation">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
        <div class="admin-breadcrumb">Administration <span>/</span> <strong>@yield('title', 'Overview')</strong></div>
    </div>

    <div class="admin-topbar-right admin-header-actions">
        <button type="button" class="admin-header-search-trigger" data-admin-open="search" aria-label="Search admin pages" aria-haspopup="dialog" aria-controls="admin-header-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span>Search</span>
            <kbd>⌘ K</kbd>
        </button>
        <button type="button" class="admin-header-icon-button" data-admin-open="notifications" aria-controls="admin-notifications-drawer" aria-haspopup="dialog" aria-label="Approval notifications{{ $headerSummary['total'] > 0 ? ', ' . $headerSummary['total'] . ' items requiring review' : '' }}">
            <i class="bi bi-bell" aria-hidden="true"></i>
            @if ($headerSummary['total'] > 0)
                <span class="admin-header-notice-count" aria-hidden="true">{{ $headerSummary['total'] > 99 ? '99+' : $headerSummary['total'] }}</span>
            @endif
        </button>
        <button type="button" class="admin-header-icon-button" data-admin-open="settings" aria-controls="admin-settings-drawer" aria-haspopup="dialog" aria-label="Open appearance settings">
            <i class="bi bi-gear" aria-hidden="true"></i>
        </button>
        <span class="admin-header-action-divider" aria-hidden="true"></span>
        <details class="admin-header-profile" id="admin-header-profile">
            <summary class="admin-header-profile-trigger" aria-label="Account menu for {{ auth()->user()->name }}">
                <span class="admin-avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </summary>
            <div class="admin-header-profile-menu">
                <div class="admin-header-profile-intro">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>Platform administrator</span>
                </div>
                <a href="{{ url('/') }}"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> View website</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"><i class="bi bi-box-arrow-right" aria-hidden="true"></i> Sign out</button>
                </form>
            </div>
        </details>
    </div>
</header>
