<div class="admin-header-overlay" id="admin-header-overlay" aria-hidden="true"></div>

<aside class="admin-head-drawer" id="admin-notifications-drawer" role="dialog" aria-modal="true" aria-labelledby="admin-notifications-title" aria-hidden="true" tabindex="-1">
    <div class="admin-head-drawer-head">
        <div>
            <p class="admin-head-overline">SPACE / ADMINISTRATION</p>
            <h2 id="admin-notifications-title">Notifications</h2>
        </div>
        <button type="button" class="admin-head-dismiss" data-admin-close aria-label="Close notifications"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="admin-head-drawer-intro">
        <p>Items awaiting review in your administration queue.</p>
        <div class="admin-notice-tabs" role="group" aria-label="Filter approval alerts">
            <button type="button" class="is-selected" data-admin-notice-filter="all" aria-pressed="true">All <span>{{ $headerSummary['total'] }}</span></button>
            <button type="button" data-admin-notice-filter="partners" aria-pressed="false">Partners</button>
            <button type="button" data-admin-notice-filter="listings" aria-pressed="false">Listings</button>
        </div>
    </div>
    <div class="admin-head-drawer-scroll" id="admin-notice-list">
        @forelse ($headerSummary['items'] as $notice)
            @php
                $noticeUrl = \Illuminate\Support\Facades\Route::has('admin.approvals.index')
                    ? route('admin.approvals.index', ['type' => $notice['type'], 'status' => 'pending'])
                    : ($notice['type'] === 'partners'
                        ? route('admin.partners.index', ['status' => 'pending'])
                        : route('admin.catalog.index', ['type' => $notice['type']]));
            @endphp
            <a href="{{ $noticeUrl }}" class="admin-notice-row" data-admin-notice-kind="{{ $notice['type'] === 'partners' ? 'partners' : 'listings' }}">
                <span class="admin-notice-symbol"><i class="bi {{ $notice['icon'] }}" aria-hidden="true"></i></span>
                <span class="admin-notice-copy">
                    <strong>{{ $notice['title'] }}</strong>
                    <span>{{ $notice['description'] }}</span>
                    <small>{{ $notice['date'] }}</small>
                </span>
                <i class="bi bi-chevron-right admin-notice-chevron" aria-hidden="true"></i>
            </a>
        @empty
            <div class="admin-head-empty" data-admin-notices-empty>
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <strong>You're all caught up</strong>
                <p>There are no items awaiting review.</p>
            </div>
        @endforelse
        @if(count($headerSummary['items']) > 0)
            <div class="admin-head-empty" id="admin-notices-filter-empty" hidden>
                <i class="bi bi-inbox" aria-hidden="true"></i>
                <strong>No matching alerts</strong>
                <p>Try another filter.</p>
            </div>
        @endif
    </div>
    <div class="admin-head-drawer-foot">
        @if (\Illuminate\Support\Facades\Route::has('admin.approvals.index'))
            <a href="{{ route('admin.approvals.index') }}" class="admin-head-primary-link">Open approval queue <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        @else
            <a href="{{ route('admin.partners.index') }}" class="admin-head-primary-link">Review partners <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
        @endif
        <small>Counts reflect reviewable records, not unread messages.</small>
    </div>
</aside>

<aside class="admin-head-drawer" id="admin-settings-drawer" role="dialog" aria-modal="true" aria-labelledby="admin-settings-title" aria-hidden="true" tabindex="-1">
    <div class="admin-head-drawer-head">
        <div>
            <p class="admin-head-overline">PERSONALIZE YOUR WORKSPACE</p>
            <h2 id="admin-settings-title">Settings</h2>
        </div>
        <button type="button" class="admin-head-dismiss" data-admin-close aria-label="Close settings"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
    </div>
    <div class="admin-head-settings-content">
        <div class="admin-head-settings-heading">
            <h3>Appearance</h3>
            <p>Adjust the administration interface. Changes stay in this browser.</p>
        </div>
        <label class="admin-head-setting">
            <span class="admin-head-setting-icon"><i class="bi bi-arrows-collapse" aria-hidden="true"></i></span>
            <span class="admin-head-setting-description"><strong>Compact layout</strong><small>Reduce spacing in navigation and tables</small></span>
            <input type="checkbox" id="admin-setting-compact" class="admin-head-switch-input" data-admin-setting="compact">
            <span class="admin-head-switch" aria-hidden="true"></span>
        </label>
        <label class="admin-head-setting">
            <span class="admin-head-setting-icon"><i class="bi bi-circle-half" aria-hidden="true"></i></span>
            <span class="admin-head-setting-description"><strong>High contrast</strong><small>Make borders and text more distinct</small></span>
            <input type="checkbox" id="admin-setting-contrast" class="admin-head-switch-input" data-admin-setting="contrast">
            <span class="admin-head-switch" aria-hidden="true"></span>
        </label>
        <label class="admin-head-setting">
            <span class="admin-head-setting-icon"><i class="bi bi-moon-stars" aria-hidden="true"></i></span>
            <span class="admin-head-setting-description"><strong>Reduce motion</strong><small>Minimize transitions and animations</small></span>
            <input type="checkbox" id="admin-setting-motion" class="admin-head-switch-input" data-admin-setting="motion">
            <span class="admin-head-switch" aria-hidden="true"></span>
        </label>
        <div class="admin-head-settings-note">
            <i class="bi bi-palette" aria-hidden="true"></i>
            <div><strong>SPACE Light</strong><span>The current clean, light theme is preserved throughout the dashboard.</span></div>
        </div>
    </div>
    <div class="admin-head-drawer-foot">
        <button type="button" id="admin-settings-reset" class="admin-head-reset-button"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset appearance</button>
    </div>
</aside>

<section class="admin-head-search-dialog" id="admin-header-search" role="dialog" aria-modal="true" aria-labelledby="admin-head-search-title" aria-hidden="true" tabindex="-1">
    <h2 class="sp-sr-only" id="admin-head-search-title">Search admin pages</h2>
    <div class="admin-head-search-field">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input type="search" id="admin-head-search-input" placeholder="Search admin pages..." aria-label="Search admin pages" autocomplete="off" spellcheck="false">
        <kbd>Esc</kbd>
    </div>
    <div class="admin-head-search-results" id="admin-head-search-results">
        @foreach ([
            ['name' => 'Dashboard', 'key' => 'Overview', 'icon' => 'bi-grid-1x2', 'route' => 'admin.dashboard'],
            ['name' => 'Analytics & Traffic', 'key' => 'Overview', 'icon' => 'bi-graph-up-arrow', 'route' => 'admin.analytics.index'],
            ['name' => 'Users', 'key' => 'Management', 'icon' => 'bi-people', 'route' => 'admin.users.index'],
            ['name' => 'Categories', 'key' => 'Management', 'icon' => 'bi-tags', 'route' => 'admin.categories.index'],
            ['name' => 'Partners', 'key' => 'Management', 'icon' => 'bi-buildings', 'route' => 'admin.partners.index'],
            ['name' => 'Studios & Services', 'key' => 'Management', 'icon' => 'bi-camera', 'route' => 'admin.catalog.index'],
            ['name' => 'Bookings', 'key' => 'Operations', 'icon' => 'bi-calendar-check', 'route' => 'admin.operations.bookings'],
            ['name' => 'Transactions', 'key' => 'Operations', 'icon' => 'bi-credit-card', 'route' => 'admin.operations.transactions'],
            ['name' => 'Reviews', 'key' => 'Operations', 'icon' => 'bi-star', 'route' => 'admin.operations.reviews'],
            ['name' => 'Approval Requests', 'key' => 'Moderation', 'icon' => 'bi-patch-check', 'route' => 'admin.approvals.index'],
        ] as $page)
            @if (\Illuminate\Support\Facades\Route::has($page['route']))
                <a href="{{ route($page['route']) }}" class="admin-head-search-result" data-admin-search-text="{{ strtolower($page['name'] . ' ' . $page['key']) }}">
                    <i class="bi {{ $page['icon'] }}" aria-hidden="true"></i>
                    <span><strong>{{ $page['name'] }}</strong><small>{{ $page['key'] }}</small></span>
                    <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                </a>
            @endif
        @endforeach
        <div class="admin-head-search-none" id="admin-head-search-none" hidden>No matching admin pages.</div>
    </div>
    <div class="admin-head-search-help"><span>↑ ↓ to navigate</span><span>↵ to open</span><span>Esc to close</span></div>
</section>
