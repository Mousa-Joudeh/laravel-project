@extends('layouts.admin')
@section('title', 'Analytics & Traffic')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/data-table.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/management.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/analytics.css') }}">
@endpush
@section('content')
<div class="admin-page-heading"><div><span class="admin-kicker">OVERVIEW / ANALYTICS</span><h1>Analytics & Traffic</h1><p>Understand page demand and the journeys people take through SPACE.</p></div>
    <form action="{{ route('admin.analytics.index') }}" method="GET" class="mgmt-period"><label for="mgmt-traffic-period">Reporting period</label><select id="mgmt-traffic-period" name="period" class="sp-select" onchange="this.form.requestSubmit()"><option value="7" @selected($days === 7)>Last 7 days</option><option value="30" @selected($days === 30)>Last 30 days</option><option value="90" @selected($days === 90)>Last 90 days</option></select></form>
</div>
<div class="mgmt-page" data-management="analytics">
    @php
        $trafficChange = $previous > 0 ? round(($total - $previous) / $previous * 100, 1) : null;
    @endphp
    <div class="mgmt-summary traffic-summary">
        <div><span>Recorded page views</span><strong>{{ number_format($total) }}</strong><small>{{ $trafficChange === null ? ($total > 0 ? 'New tracked activity' : 'No activity') : (($trafficChange > 0 ? '+' : '') . number_format($trafficChange, 1) . '% vs prior period') }}</small></div>
        <div><span>Guest page views</span><strong>{{ number_format($guests) }}</strong><small>Without a linked user ID</small></div>
        <div><span>Member page views</span><strong>{{ number_format($loggedIn) }}</strong><small>Linked to registered users</small></div>
        <div><span>Distinct known members</span><strong>{{ number_format($knownUsers) }}</strong><small>Does not count anonymous people</small></div>
    </div>
    <div class="traffic-charts">
        <section class="sp-card traffic-chart-card"><div class="traffic-panel-head"><div><h2>Visits over time</h2><p>Daily recorded views from public pages</p></div><span>Last {{ $days }} days</span></div>
            <div class="traffic-bar-chart" role="img" aria-label="Daily tracked page views">
                @foreach($trend as $index => $point)
                    <div class="traffic-bar-column" title="{{ $point['label'] }}: {{ number_format($point['value']) }} views"><div class="traffic-bar-track"><div class="traffic-bar" style="height: {{ $point['value'] > 0 ? max(3, round($point['value'] / $maxTrend * 100)) : 0 }}%"></div></div>@if($days === 7 || $index % ($days === 30 ? 6 : 15) === 0)<small>{{ $point['label'] }}</small>@else<small aria-hidden="true">&nbsp;</small>@endif</div>
                @endforeach
            </div>
            @if($total === 0)<p class="traffic-empty">No page visits have been recorded for this period.</p>@endif
        </section>
        <section class="sp-card traffic-popular"><div class="traffic-panel-head"><div><h2>Most visited pages</h2><p>Based on recorded request paths</p></div></div>
            <div class="traffic-popular-list">
                @forelse($popular as $item)
                    <div class="traffic-popular-item"><div class="traffic-popular-top"><strong title="{{ $item->path }}">/{{ ltrim($item->path, '/') }}</strong><span>{{ number_format($item->views) }}</span></div><div class="traffic-progress"><span style="width: {{ round($item->views / $topMax * 100) }}%"></span></div></div>
                @empty
                    <p class="traffic-empty">No tracked public pages yet.</p>
                @endforelse
            </div>
        </section>
    </div>
    <section class="sp-card mgmt-card">
        <div class="traffic-panel-head traffic-log-head"><div><h2>Visit log</h2><p>Filter recorded activity; each row represents one page view.</p></div></div>
        <div class="mgmt-toolbar">
            <form method="GET" action="{{ route('admin.analytics.index') }}" class="mgmt-filters">
                <input type="hidden" name="period" value="{{ $days }}"><input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="direction" value="{{ $direction }}"><input type="hidden" name="per_page" value="{{ $visits->perPage() }}">
                <label class="mgmt-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $search }}" placeholder="Search path or route..." aria-label="Search visits"></label>
                <select name="visitor" class="sp-select" aria-label="Visitor type"><option value="">All visitors</option><option value="guest" @selected($visitor === 'guest')>Guest</option><option value="member" @selected($visitor === 'member')>Signed in</option></select>
                <button type="submit" class="sp-btn sp-btn-primary">Apply</button><a href="{{ route('admin.analytics.index', ['period' => $days]) }}" class="sp-btn sp-btn-secondary">Reset</a>
            </form>
            <button type="button" class="sp-icon-button" data-mgmt-menu="mgmt-traffic-tools" aria-label="Table options"><i class="bi bi-three-dots-vertical"></i></button><div class="mgmt-menu" id="mgmt-traffic-tools" popover="auto"><button type="button" class="mgmt-menu-item" data-mgmt-print><i class="bi bi-printer"></i>Print table</button><a class="mgmt-menu-item" href="{{ route('admin.csv.export', array_merge(request()->except('page'), ['section' => 'traffic'])) }}" role="menuitem"><i class="bi bi-download"></i>Export CSV</a></div>
        </div>
        <x-admin.data-table :records="$visits" id="traffic-table">
            <x-slot:head><tr><th><x-admin.sort-link name="path" label="Page" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="route_name" label="Route" :sort="$sort" :direction="$direction" /></th><th>Visitor</th><th><x-admin.sort-link name="visited_at" label="Visited" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr></x-slot:head>
            @forelse($visits as $visit)
                <tr data-record-id="{{ $visit->id }}" data-record-name="/{{ ltrim($visit->path, '/') }}" data-record-route="{{ $visit->route_name }}" data-record-owner="{{ $visit->user?->name }}" data-record-email="{{ $visit->user?->email }}" data-record-type="{{ $visit->user_id ? 'Signed in' : 'Guest' }}" data-record-time="{{ $visit->visited_at?->format('d M Y, H:i:s') }}">
                    <td><strong class="mgmt-number">/{{ ltrim($visit->path, '/') }}</strong></td><td>{{ $visit->route_name ?: '—' }}</td><td>{{ $visit->user?->name ?? 'Guest' }}</td><td>{{ $visit->visited_at?->format('d M Y · H:i') ?? '—' }}</td>
                    <td class="mgmt-actions"><button type="button" class="sp-icon-button" data-mgmt-menu="mgmt-visit-{{ $visit->id }}" aria-label="More visit details"><i class="bi bi-three-dots-vertical"></i></button><div class="mgmt-menu" id="mgmt-visit-{{ $visit->id }}" popover="auto"><button type="button" class="mgmt-menu-item" data-mgmt-action="visit-view"><i class="bi bi-eye"></i>View visit</button></div></td>
                </tr>
            @empty
                <tr><td colspan="5" class="mgmt-empty">No visits match your filters.</td></tr>
            @endforelse
        </x-admin.data-table>
    </section>
    <p class="mgmt-hint traffic-disclaimer">Traffic figures include only rows currently written to <code>page_visits</code>. They are page views, not unique visitors, conversions, ad attribution, or analytics from third-party platforms. If no route is recording visits, the figures will remain zero.</p>
</div>
<dialog id="mgmt-visit-dialog" class="mgmt-dialog" aria-labelledby="mgmt-visit-title"><div class="mgmt-dialog-head"><div><h2 id="mgmt-visit-title">Visit details</h2><p>Recorded page access</p></div><button type="button" class="sp-icon-button" data-mgmt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div><div class="mgmt-dialog-body"><div class="mgmt-detail-grid"><div><span>Path</span><strong id="mgmt-visit-path"></strong></div><div><span>Route name</span><strong id="mgmt-visit-route"></strong></div><div><span>Visitor type</span><strong id="mgmt-visit-type"></strong></div><div><span>Member</span><strong id="mgmt-visit-owner"></strong></div><div><span>Email</span><strong id="mgmt-visit-email"></strong></div><div><span>Time</span><strong id="mgmt-visit-time"></strong></div></div></div><div class="mgmt-dialog-footer"><button class="sp-btn sp-btn-secondary" type="button" data-mgmt-close>Close</button></div></dialog>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/management.js') }}" defer></script>
@endpush
