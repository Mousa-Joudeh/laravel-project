@extends('layouts.admin')

@section('title', 'Dashboard')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
@endpush

@section('content')
<div class="dash">
    <header class="dash-header">
        <div>
            <span class="admin-kicker">SPACE / PLATFORM INTELLIGENCE</span>
            <h1>Platform overview</h1>
            <p>Business performance, partner growth and operational health.</p>
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}" class="dash-period-form">
            <label for="dash-period">Reporting period</label>
            <select id="dash-period" name="period" aria-label="Reporting period" onchange="this.form.requestSubmit()">
                <option value="7" @selected($days === 7)>Last 7 days</option>
                <option value="30" @selected($days === 30)>Last 30 days</option>
                <option value="90" @selected($days === 90)>Last 90 days</option>
            </select>
        </form>
    </header>

    <nav class="dash-quick-access" aria-label="Quick access">
        <a href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i> Manage users <i class="bi bi-arrow-up-right"></i></a>
        <a href="#dash-approvals"><i class="bi bi-patch-check"></i> Approval queue @if($pendingCount > 0)<span>{{ $pendingCount }}</span>@endif</a>
        <a href="#dash-partners"><i class="bi bi-buildings"></i> Partner performance</a>
        <a href="#dash-bookings"><i class="bi bi-calendar-check"></i> Bookings</a>
    </nav>

    <section class="dash-kpi-grid" aria-label="Core business indicators">
        @foreach($kpis as $kpi)
            <article class="dash-kpi dash-kpi-{{ $kpi['key'] }}">
                <div class="dash-kpi-head"><span>{{ $kpi['label'] }}</span><i class="bi bi-{{ $kpi['icon'] }}"></i></div>
                <strong>{{ $kpi['value'] }}</strong>
                <div class="dash-kpi-foot">
                    <span class="dash-change is-{{ $kpi['change']['direction'] }}"><i class="bi bi-{{ $kpi['change']['icon'] }}"></i>{{ $kpi['change']['text'] }}</span>
                    <span>vs previous {{ $days }} days</span>
                </div>
                <p>{{ $kpi['note'] }}</p>
            </article>
        @endforeach
    </section>

    <section class="dash-main-grid" id="dash-performance" aria-label="Business trends">
        <article class="dash-card dash-trend-card">
            <div class="dash-card-head">
                <div><h2>Business performance</h2><p>How SPACE is performing over the selected period</p></div>
                <div class="dash-chart-tabs" role="group" aria-label="Chart metric">
                    <button type="button" class="is-active" data-dash-metric="payments" aria-pressed="true">Payments</button>
                    <button type="button" data-dash-metric="bookings" aria-pressed="false">Bookings</button>
                </div>
            </div>
            <div class="dash-chart-summary">
                <span class="dash-legend-dot"></span>
                <span id="dash-chart-series-label">Net payments · JOD</span>
                <strong id="dash-chart-series-total">—</strong>
            </div>
            <div class="dash-chart-container">
                <svg id="dash-performance-chart" viewBox="0 0 740 255" preserveAspectRatio="none" role="img" aria-label="Business performance chart"></svg>
                <div id="dash-chart-empty" class="dash-chart-empty" hidden>No activity recorded in this period.</div>
            </div>
            <div id="dash-chart-labels" class="dash-chart-labels" aria-hidden="true"></div>
            <script type="application/json" id="dash-activity-data">@json($activity->values())</script>
        </article>

        <article class="dash-card dash-distribution-card">
            <div class="dash-card-head"><div><h2>Booking health</h2><p>Reservations by current status</p></div></div>
            @php($offset = 0)
            <div class="dash-donut-wrap">
                <svg viewBox="0 0 180 180" class="dash-donut" role="img" aria-label="Booking status distribution">
                    <circle cx="90" cy="90" r="65" fill="none" stroke="#edf1f3" stroke-width="16"></circle>
                    @foreach($bookingStatuses as $status)
                        @if($status['count'] > 0)
                            <circle cx="90" cy="90" r="65" fill="none" stroke="{{ $status['color'] }}" stroke-width="16" stroke-dasharray="{{ round($status['percentage'] / 100 * 408.41, 2) }} 408.41" stroke-dashoffset="{{ round(-$offset / 100 * 408.41, 2) }}" transform="rotate(-90 90 90)"></circle>
                        @endif
                        @php($offset += $status['percentage'])
                    @endforeach
                </svg>
                <div class="dash-donut-center"><strong>{{ number_format($current['bookings']) }}</strong><span>Bookings</span></div>
            </div>
            <div class="dash-status-legend">
                @foreach($bookingStatuses as $status)
                    <div><span class="dash-legend-name"><i style="--legend-color: {{ $status['color'] }}"></i>{{ $status['label'] }}</span><strong>{{ number_format($status['count']) }}</strong></div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="dash-secondary-grid" aria-label="Additional business indicators">
        @foreach($secondaryKpis as $item)
            <article class="dash-card dash-secondary-card">
                <div class="dash-secondary-top"><span>{{ $item['label'] }}</span><i class="bi bi-{{ $item['icon'] }}"></i></div>
                <div class="dash-secondary-value"><strong>{{ $item['value'] }}</strong><span class="dash-change is-{{ $item['change']['direction'] }}"><i class="bi bi-{{ $item['change']['icon'] }}"></i>{{ $item['change']['text'] }}</span></div>
                <p>{{ $item['note'] }} <span>· vs previous period</span></p>
            </article>
        @endforeach
    </section>

    <section class="dash-ops-grid" aria-label="Operations and partner performance">
        <article class="dash-card" id="dash-approvals">
            <div class="dash-card-head"><div><h2>Action required</h2><p>Pending requests awaiting an admin review</p></div><span class="dash-count-pill">{{ $pendingCount }} pending</span></div>
            <div class="dash-approval-list">
                @foreach($approvals as $approval)
                    <div class="dash-approval-row"><span class="dash-approval-icon"><i class="bi bi-{{ $approval['icon'] }}"></i></span><span>{{ $approval['label'] }}</span><strong>{{ number_format($approval['count']) }}</strong></div>
                @endforeach
            </div>
            <p class="dash-card-note">Approvals are informational until management pages are ready.</p>
        </article>

        <article class="dash-card" id="dash-partners">
            <div class="dash-card-head"><div><h2>Partner performance</h2><p>Approved providers ranked by bookings this period</p></div><span class="dash-panel-stat">{{ $platformStats['approved_partners'] }} active partners</span></div>
            <div class="dash-partner-list">
                @forelse($topPartners as $index => $partner)
                    <div class="dash-partner-row">
                        <span class="dash-partner-rank">{{ $index + 1 }}</span>
                        <span class="dash-partner-avatar">{{ mb_strtoupper(mb_substr($partner->name, 0, 1)) }}</span>
                        <span class="dash-partner-name"><strong>{{ $partner->providerProfile?->company_name ?: $partner->name }}</strong><small>{{ $partner->name }}</small></span>
                        <span class="dash-partner-bookings">{{ number_format($partner->period_bookings_count) }} <small>bookings</small></span>
                    </div>
                @empty
                    <div class="dash-empty">No approved partners to display.</div>
                @endforelse
            </div>
        </article>
    </section>

    <section class="dash-card dash-bookings-card" id="dash-bookings">
        <div class="dash-card-head">
            <div><h2>Recent bookings</h2><p>Search and review reservations created in the selected period</p></div>
            @if(Route::has('bookings.index'))
                <a class="dash-see-all" href="{{ route('bookings.index') }}">View all <i class="bi bi-arrow-up-right"></i></a>
            @endif
        </div>
        <form method="GET" action="{{ route('admin.dashboard') }}#dash-bookings" class="dash-table-toolbar">
            <input type="hidden" name="period" value="{{ $days }}">
            <label class="dash-table-search"><i class="bi bi-search"></i><input type="search" name="booking_search" value="{{ $bookingSearch }}" placeholder="Search reference, customer, studio..." aria-label="Search bookings"></label>
            <select name="booking_status" aria-label="Filter booking status">
                <option value="">All statuses</option>
                @foreach($bookingStatuses as $status)
                    <option value="{{ $status['key'] }}" @selected($bookingStatus === $status['key'])>{{ $status['label'] }}</option>
                @endforeach
            </select>
            <button type="submit" class="dash-filter-btn">Apply</button>
            @if($bookingSearch !== '' || $bookingStatus !== '')
                <a class="dash-filter-reset" href="{{ route('admin.dashboard', ['period' => $days]) }}#dash-bookings">Reset</a>
            @endif
        </form>
        <div class="dash-table-overflow">
            <table class="dash-table">
                <thead><tr><th>Booking</th><th>Customer</th><th>Studio</th><th>Scheduled</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($recentBookings as $booking)
                        <tr>
                            <td class="dash-booking-ref">{{ $booking->reference }}</td>
                            <td>{{ $booking->customer?->name ?? '—' }}</td>
                            <td>{{ $booking->studio?->name ?? '—' }}</td>
                            <td>{{ $booking->starts_at?->format('d M Y') ?? '—' }}</td>
                            <td class="dash-booking-amount">{{ number_format((float) $booking->total_price, 2) }} <span>{{ $booking->currency }}</span></td>
                            <td><span class="dash-status is-{{ $booking->status }}">{{ ucwords(str_replace('_', ' ', $booking->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="dash-empty">No bookings match this period and filter.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="dash-table-footer">
            <span>Showing {{ $recentBookings->firstItem() ?? 0 }}–{{ $recentBookings->lastItem() ?? 0 }} of {{ $recentBookings->total() }}</span>
            <div class="dash-page-actions">
                @if($recentBookings->previousPageUrl())<a href="{{ $recentBookings->previousPageUrl() }}#dash-bookings" aria-label="Previous bookings page"><i class="bi bi-chevron-left"></i></a>@else<span class="is-disabled"><i class="bi bi-chevron-left"></i></span>@endif
                <span>{{ $recentBookings->currentPage() }} / {{ $recentBookings->lastPage() }}</span>
                @if($recentBookings->nextPageUrl())<a href="{{ $recentBookings->nextPageUrl() }}#dash-bookings" aria-label="Next bookings page"><i class="bi bi-chevron-right"></i></a>@else<span class="is-disabled"><i class="bi bi-chevron-right"></i></span>@endif
            </div>
        </div>
    </section>

    <section class="dash-bottom-grid" aria-label="Marketing and platform coverage">
        <article class="dash-card">
            <div class="dash-card-head"><div><h2>Most visited pages</h2><p>Tracked non-admin traffic in this period</p></div><i class="bi bi-graph-up dash-header-icon"></i></div>
            <div class="dash-page-list">
                @forelse($topPages as $page)
                    <div class="dash-page-row"><span><strong>{{ ucwords(str_replace(['.', '_', '-'], ' ', $page->route_name)) }}</strong><small>{{ $page->route_name }}</small></span><b>{{ number_format($page->total) }}</b></div>
                @empty
                    <div class="dash-empty">No tracked visits in this period.</div>
                @endforelse
            </div>
        </article>
        <article class="dash-card">
            <div class="dash-card-head"><div><h2>Marketplace snapshot</h2><p>Available scale and customer feedback</p></div></div>
            <div class="dash-snapshot-grid">
                <div><i class="bi bi-building-check"></i><strong>{{ number_format($platformStats['approved_studios']) }}</strong><span>Active studios</span></div>
                <div><i class="bi bi-star"></i><strong>{{ number_format($platformStats['reviews']) }}</strong><span>Customer reviews</span></div>
                <div><i class="bi bi-people"></i><strong>{{ number_format($platformStats['approved_partners']) }}</strong><span>Active partners</span></div>
            </div>
            <p class="dash-card-note">Payment volume is not SPACE profit. Visits are page views, not unique visitors.</p>
        </article>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/admin/dashboard.js') }}" defer></script>
@endpush
