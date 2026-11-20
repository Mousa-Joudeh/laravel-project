@extends('layouts.admin')
@section('title', 'Partners')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/data-table.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/management.css') }}">
@endpush
@section('content')
<div class="admin-page-heading"><div><span class="admin-kicker">MANAGEMENT / PARTNERS</span><h1>Partners</h1><p>Review provider applications and manage partner approvals.</p></div><a href="{{ route('admin.dashboard') }}#dash-partners" class="sp-btn sp-btn-secondary"><i class="bi bi-graph-up"></i> Platform overview</a></div>
<div class="mgmt-page" data-management="partners">
    <section class="sp-card mgmt-card">
        @include('admin.partials.management-tabs', ['tabs' => ['' => ['All', $counts['all']], 'pending' => ['Pending', $counts['pending']], 'approved' => ['Approved', $counts['approved']], 'rejected' => ['Rejected', $counts['rejected']]], 'selected' => $status, 'route' => 'admin.partners.index', 'param' => 'status'])
        <div class="mgmt-toolbar">
            <form class="mgmt-filters" method="GET" action="{{ route('admin.partners.index') }}">
                <input type="hidden" name="status" value="{{ $status }}"><input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="direction" value="{{ $direction }}"><input type="hidden" name="per_page" value="{{ $partners->perPage() }}">
                <label class="mgmt-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $search }}" placeholder="Company, owner, city or email..." aria-label="Search partners"></label>
                <button type="submit" class="sp-btn sp-btn-primary">Search</button><a href="{{ route('admin.partners.index') }}" class="sp-btn sp-btn-secondary">Reset</a>
            </form>
            <button type="button" class="sp-icon-button" data-mgmt-menu="mgmt-partner-tools" aria-label="Table options"><i class="bi bi-three-dots-vertical"></i></button>
            <div class="mgmt-menu" id="mgmt-partner-tools" popover="auto"><button class="mgmt-menu-item" type="button" data-mgmt-print><i class="bi bi-printer"></i>Print table</button><a class="mgmt-menu-item" href="{{ route('admin.csv.export', array_merge(request()->except('page'), ['section' => 'partners'])) }}" role="menuitem"><i class="bi bi-download"></i>Export CSV</a></div>
        </div>
        <x-admin.data-table :records="$partners" id="partner-table">
            <x-slot:head>
                <tr><th><x-admin.sort-link name="company_name" label="Partner" :sort="$sort" :direction="$direction" /></th><th>Owner</th><th><x-admin.sort-link name="city" label="City" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="status" label="Status" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="created_at" label="Applied" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr>
            </x-slot:head>
            @forelse($partners as $partner)
                <tr data-record-id="{{ $partner->id }}" data-record-name="{{ $partner->company_name }}" data-record-status="{{ $partner->status }}" data-record-city="{{ $partner->city }}" data-record-owner="{{ $partner->user?->name }}" data-record-email="{{ $partner->user?->email }}" data-record-phone="{{ $partner->user?->phone }}" data-record-owner-status="{{ $partner->user?->status }}" data-record-owner-role="{{ $partner->user?->role }}" data-record-tagline="{{ $partner->tagline }}" data-record-bio="{{ $partner->bio }}" data-record-instagram="{{ $partner->instagram }}" data-record-note="{{ $partner->admin_note }}" data-record-joined="{{ $partner->created_at?->format('d M Y') }}" data-review-url="{{ route('admin.partners.review', $partner) }}">
                    <td><div class="mgmt-name-cell"><span class="mgmt-record-icon"><i class="bi bi-building"></i></span><div><strong>{{ $partner->company_name }}</strong><small>{{ $partner->slug }}</small></div></div></td>
                    <td><div class="mgmt-two-lines"><strong>{{ $partner->user?->name ?? '—' }}</strong><small>{{ $partner->user?->email ?? '—' }}</small></div></td>
                    <td>{{ $partner->city ?: '—' }}</td>
                    <td><span class="sp-badge {{ $partner->status === 'approved' ? 'sp-badge-success' : ($partner->status === 'rejected' ? 'sp-badge-danger' : 'sp-badge-warning') }}">{{ ucfirst($partner->status) }}</span></td>
                    <td>{{ $partner->created_at?->format('d M Y') ?? '—' }}</td>
                    <td class="mgmt-actions"><button type="button" class="sp-icon-button" data-mgmt-action="partner-view" title="View partner" aria-label="View {{ $partner->company_name }}"><i class="bi bi-eye"></i></button><button type="button" class="sp-icon-button" data-mgmt-menu="mgmt-partner-{{ $partner->id }}" title="More" aria-label="More about {{ $partner->company_name }}"><i class="bi bi-three-dots-vertical"></i></button>
                        <div class="mgmt-menu" id="mgmt-partner-{{ $partner->id }}" popover="auto"><button class="mgmt-menu-item" type="button" data-mgmt-action="partner-view"><i class="bi bi-eye"></i>View details</button>@if($partner->status === 'pending')<button class="mgmt-menu-item" type="button" data-mgmt-action="partner-review"><i class="bi bi-patch-check"></i>Review request</button>@endif</div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="mgmt-empty">No partner profiles match the filters.</td></tr>
            @endforelse
        </x-admin.data-table>
    </section>
</div>
<dialog id="mgmt-partner-dialog" class="mgmt-dialog mgmt-detail-dialog" aria-labelledby="mgmt-partner-title">
    <div class="mgmt-dialog-head"><div><h2 id="mgmt-partner-title">Partner details</h2><p>Review partner application</p></div><button type="button" class="sp-icon-button" data-mgmt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
    <div class="mgmt-dialog-body">
        <div class="mgmt-detail-hero"><div class="mgmt-detail-avatar"><i class="bi bi-buildings"></i></div><div><h3 id="mgmt-partner-name"></h3><p id="mgmt-partner-owner"></p><span class="sp-badge" id="mgmt-partner-status"></span></div></div>
        <div class="mgmt-detail-grid"><div><span>City</span><strong id="mgmt-partner-city"></strong></div><div><span>Email</span><strong id="mgmt-partner-email"></strong></div><div><span>Phone</span><strong id="mgmt-partner-phone"></strong></div><div><span>Owner status</span><strong id="mgmt-partner-owner-status"></strong></div><div><span>Submitted</span><strong id="mgmt-partner-joined"></strong></div><div><span>Instagram</span><strong id="mgmt-partner-instagram"></strong></div></div>
        <div class="mgmt-detail-text"><span>Business description</span><p id="mgmt-partner-bio"></p></div>
        <div class="mgmt-detail-text"><span>Existing admin note</span><p id="mgmt-partner-existing-note"></p></div>
        <form id="mgmt-partner-review-form" method="POST" class="mgmt-inline-review" hidden>
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" id="mgmt-partner-new-status" value="">
            <div class="mgmt-field"><label for="mgmt-partner-note">Review note</label><textarea id="mgmt-partner-note" name="admin_note" maxlength="255" rows="2" placeholder="Reason or feedback to the applicant"></textarea></div>
            <div class="mgmt-form-alert" data-mgmt-errors hidden role="alert"></div>
            <div class="mgmt-review-actions"><button class="sp-btn sp-btn-danger" type="button" data-partner-review="rejected">Reject</button><button class="sp-btn sp-btn-primary" type="button" data-partner-review="approved">Approve</button></div>
        </form>
        <p class="mgmt-hint" id="mgmt-partner-readonly" hidden>Only pending requests can be reviewed with the existing provider workflow.</p>
    </div>
    <div class="mgmt-dialog-footer"><button type="button" class="sp-btn sp-btn-secondary" data-mgmt-close>Close</button></div>
</dialog>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/management.js') }}" defer></script>
@endpush
