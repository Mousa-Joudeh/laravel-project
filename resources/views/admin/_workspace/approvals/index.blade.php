@extends('layouts.admin')
@section('title', 'Approval Requests')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/data-table.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/management.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/operations.css') }}">
@endpush
@section('content')
<div class="admin-page-heading">
    <div><span class="admin-kicker">MODERATION / APPROVALS</span><h1>Approval Requests</h1><p>Review partner applications and marketplace listings before publication.</p></div>
</div>
<div class="mgmt-page" data-management="approvals">
    <section class="sp-card mgmt-card">
        <div class="mgmt-tabs" role="navigation" aria-label="Approval types">
            @foreach(['partners' => 'Partners', 'studios' => 'Studios', 'services' => 'Services', 'packages' => 'Packages'] as $key => $title)
                <a class="mgmt-tab {{ $type === $key ? 'is-active' : '' }}" href="{{ route('admin.approvals.index', ['type' => $key, 'status' => 'pending']) }}">{{ $title }} <span>{{ $counts[$key] }}</span></a>
            @endforeach
        </div>
        <div class="mgmt-toolbar">
            <form action="{{ route('admin.approvals.index') }}" method="GET" class="mgmt-filters">
                <input type="hidden" name="type" value="{{ $type }}">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="per_page" value="{{ $records->perPage() }}">
                <label class="mgmt-search"><i class="bi bi-search"></i><input name="search" value="{{ $search }}" type="search" placeholder="Search {{ $type }}..." aria-label="Search approvals"></label>
                <select name="status" class="sp-select" aria-label="Status">
                    @foreach(($type === 'packages' ? ['pending' => 'Inactive / Needs approval', 'approved' => 'Active', 'all' => 'All statuses'] : ['pending' => 'Pending review', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All statuses']) as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="sp-btn sp-btn-primary">Apply</button>
                <a href="{{ route('admin.approvals.index', ['type' => $type]) }}" class="sp-btn sp-btn-secondary">Reset</a>
            </form>
            <button class="sp-icon-button" type="button" data-mgmt-menu="approvals-tools" aria-label="Table options"><i class="bi bi-three-dots-vertical"></i></button>
            <div class="mgmt-menu" id="approvals-tools" popover="auto"><button type="button" class="mgmt-menu-item" data-mgmt-print><i class="bi bi-printer"></i>Print table</button><a class="mgmt-menu-item" href="{{ route('admin.csv.export', array_merge(request()->except('page'), ['section' => 'approvals'])) }}" role="menuitem"><i class="bi bi-download"></i>Export CSV</a></div>
        </div>
        <x-admin.data-table :records="$records" id="approval-table">
            <x-slot:head>
                <tr><th>Request</th><th>Owner / Studio</th><th><x-admin.sort-link name="status" label="Status" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="created_at" label="Submitted" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr>
            </x-slot:head>
            @forelse($records as $item)
                @php
                    $name = $type === 'partners' ? $item->company_name : ($type === 'services' ? $item->title : $item->name);
                    $owner = match ($type) {
                        'partners' => $item->user?->name,
                        'studios' => $item->provider?->name,
                        default => $item->studio?->name,
                    };
                    $itemStatus = $type === 'packages' ? ($item->is_active ? 'approved' : 'pending') : $item->status;
                    $reviewUrl = match ($type) {
                        'partners' => route('admin.partners.review', $item),
                        'studios' => route('admin.catalog.studios.review', $item),
                        'services' => route('admin.catalog.services.review', $item),
                        'packages' => route('admin.catalog.packages.review', $item),
                    };
                @endphp
                <tr data-record-id="{{ $item->id }}" data-record-name="{{ $name }}" data-record-type="{{ $type }}" data-record-owner="{{ $owner }}" data-record-status="{{ $itemStatus }}" data-record-note="{{ $type === 'packages' ? '' : $item->admin_note }}" data-record-description="{{ $item->description ?? $item->bio ?? '' }}" data-record-created="{{ $item->created_at?->format('d M Y, H:i') }}" data-review-url="{{ $reviewUrl }}">
                    <td><div class="mgmt-name-cell"><span class="mgmt-record-icon"><i class="bi bi-{{ ['partners' => 'people', 'studios' => 'camera', 'services' => 'layers', 'packages' => 'box-seam'][$type] }}"></i></span><div><strong>{{ $name }}</strong><small>#{{ $item->id }} · {{ ucfirst(rtrim($type, 's')) }}</small></div></div></td>
                    <td>{{ $owner ?? '—' }}</td>
                    <td><span class="sp-badge {{ $itemStatus === 'approved' ? 'sp-badge-success' : ($itemStatus === 'rejected' ? 'sp-badge-danger' : 'sp-badge-warning') }}">{{ $type === 'packages' ? ($item->is_active ? 'Active' : 'Needs approval') : ucfirst($itemStatus) }}</span></td>
                    <td>{{ $item->created_at?->format('d M Y') ?? '—' }}</td>
                    <td class="mgmt-actions">
                        <button class="sp-icon-button" type="button" data-work-action="approval-view" title="View details" aria-label="View request"><i class="bi bi-eye"></i></button>
                        <button class="sp-icon-button" type="button" data-mgmt-menu="approval-{{ $type }}-{{ $item->id }}" aria-label="More actions"><i class="bi bi-three-dots-vertical"></i></button>
                        <div class="mgmt-menu" id="approval-{{ $type }}-{{ $item->id }}" popover="auto">
                            <button class="mgmt-menu-item" type="button" data-work-action="approval-view"><i class="bi bi-eye"></i>View details</button>
                            @if($itemStatus === 'pending')<button class="mgmt-menu-item" type="button" data-work-action="approval-review"><i class="bi bi-patch-check"></i>Review request</button>@endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td class="mgmt-empty" colspan="5">No {{ $type }} match the selected filters.</td></tr>
            @endforelse
        </x-admin.data-table>
    </section>
</div>
<dialog class="mgmt-dialog workspace-dialog" id="approval-detail" aria-labelledby="approval-title">
    <div class="mgmt-dialog-head"><div><h2 id="approval-title">Request details</h2><p id="approval-subtitle">Marketplace review</p></div><button type="button" class="sp-icon-button" data-mgmt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
    <div class="mgmt-dialog-body">
        <div class="mgmt-detail-grid"><div><span>Owner / Studio</span><strong id="approval-owner"></strong></div><div><span>Status</span><strong id="approval-status"></strong></div><div><span>Submitted</span><strong id="approval-date"></strong></div><div><span>Existing admin note</span><strong id="approval-existing-note"></strong></div></div>
        <div class="mgmt-detail-text"><span>Description</span><p id="approval-description"></p></div>
        <form id="approval-form" hidden>
            @csrf
            <input type="hidden" name="_method" value="PATCH"><input type="hidden" name="status" id="approval-new-status">
            <div class="mgmt-field"><label for="approval-note">Review note</label><textarea name="admin_note" id="approval-note" rows="3" maxlength="255" placeholder="Reason for rejection / optional approval note"></textarea></div>
            <p class="mgmt-hint" id="approval-hint">Only pending requests can be reviewed. Packages use the existing active flag; no separate rejected state is stored.</p>
            <div class="mgmt-form-alert" data-mgmt-errors hidden role="alert"></div>
            <div class="mgmt-review-actions"><button type="button" class="sp-btn sp-btn-danger" data-approval-status="rejected" id="approval-reject-button">Reject</button><button type="button" class="sp-btn sp-btn-primary" data-approval-status="approved">Approve</button></div>
        </form>
    </div>
    <div class="mgmt-dialog-footer"><button class="sp-btn sp-btn-secondary" type="button" data-mgmt-close>Close</button></div>
</dialog>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/management.js') }}" defer></script>
<script src="{{ asset('js/admin/workspace.js') }}" defer></script>
@endpush
