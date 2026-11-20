@extends('layouts.admin')
@section('title', 'Studios & Services')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/data-table.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/management.css') }}">
@endpush
@section('content')
<div class="admin-page-heading"><div><span class="admin-kicker">MANAGEMENT / CATALOG</span><h1>Studios & Services</h1><p>Review studio, service and package listings from one catalog.</p></div><a href="{{ route('admin.categories.index') }}" class="sp-btn sp-btn-secondary"><i class="bi bi-tags"></i> Categories</a></div>
<div class="mgmt-page" data-management="catalog">
    <section class="sp-card mgmt-card">
        <div class="mgmt-tabs" role="navigation" aria-label="Catalog sections">
            @foreach(['studios' => 'Studios', 'services' => 'Services', 'packages' => 'Packages'] as $option => $label)
                <a href="{{ route('admin.catalog.index', ['type' => $option]) }}" class="mgmt-tab {{ $type === $option ? 'is-active' : '' }}">{{ $label }} <span>{{ $counts[$option] }}</span></a>
            @endforeach
        </div>
        <div class="mgmt-toolbar">
            <form method="GET" action="{{ route('admin.catalog.index') }}" class="mgmt-filters">
                <input type="hidden" name="type" value="{{ $type }}"><input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="direction" value="{{ $direction }}"><input type="hidden" name="per_page" value="{{ $records->perPage() }}">
                <label class="mgmt-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $search }}" placeholder="Search {{ $type }}..." aria-label="Search catalog"></label>
                <select class="sp-select" name="status" aria-label="Filter status">
                    <option value="">All statuses</option>
                    @foreach($type === 'packages' ? ['active' => 'Active', 'inactive' => 'Inactive / Needs approval'] : ['draft' => 'Draft', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @if($type === 'studios')
                    <select class="sp-select" name="category" aria-label="Filter category"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected($categoryId === $category->id)>{{ $category->name }}</option>@endforeach</select>
                @endif
                <button class="sp-btn sp-btn-primary" type="submit">Apply</button><a href="{{ route('admin.catalog.index', ['type' => $type]) }}" class="sp-btn sp-btn-secondary">Reset</a>
            </form>
            <button type="button" class="sp-icon-button" data-mgmt-menu="mgmt-catalog-tools" aria-label="Table options"><i class="bi bi-three-dots-vertical"></i></button>
            <div class="mgmt-menu" id="mgmt-catalog-tools" popover="auto"><button type="button" class="mgmt-menu-item" data-mgmt-print><i class="bi bi-printer"></i>Print table</button><a class="mgmt-menu-item" href="{{ route('admin.csv.export', array_merge(request()->except('page'), ['section' => 'catalog'])) }}" role="menuitem"><i class="bi bi-download"></i>Export CSV</a></div>
        </div>
        <x-admin.data-table :records="$records" id="catalog-table">
            <x-slot:head>
                @if($type === 'studios')
                    <tr><th><x-admin.sort-link name="name" label="Studio" :sort="$sort" :direction="$direction" /></th><th>Provider</th><th>Category</th><th><x-admin.sort-link name="hourly_price" label="Price / hr" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="status" label="Status" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr>
                @elseif($type === 'services')
                    <tr><th><x-admin.sort-link name="title" label="Service" :sort="$sort" :direction="$direction" /></th><th>Studio</th><th>Specialty</th><th><x-admin.sort-link name="price" label="Price" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="status" label="Status" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr>
                @else
                    <tr><th><x-admin.sort-link name="name" label="Package" :sort="$sort" :direction="$direction" /></th><th>Studio</th><th>Type</th><th><x-admin.sort-link name="price" label="Price" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="is_active" label="Status" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr>
                @endif
            </x-slot:head>
            @forelse($records as $record)
                @php
                    $title = $type === 'services' ? $record->title : $record->name;
                    $statusValue = $type === 'packages' ? ($record->is_active ? 'active' : 'inactive') : $record->status;
                    $price = $type === 'studios' ? $record->hourly_price : $record->price;
                    $host = $type === 'studios' ? $record->provider?->name : $record->studio?->name;
                    $secondary = $type === 'studios' ? $record->city : ($type === 'services' ? $record->specialty : $record->type);
                    $visible = $type === 'packages' ? ($record->is_active && $record->studio?->status === 'approved' && $record->studio?->is_active && $record->studio?->provider?->status === 'active' && $record->studio?->provider?->providerProfile?->status === 'approved') : ($type === 'studios' ? $record->is_active && $record->status === 'approved' : $record->is_active && $record->status === 'approved' && $record->studio?->status === 'approved' && $record->studio?->is_active);
                @endphp
                <tr data-record-id="{{ $record->id }}" data-record-name="{{ $title }}" data-record-type="{{ $type }}" data-record-status="{{ $statusValue }}" data-record-host="{{ $host }}" data-record-city="{{ $secondary }}" data-record-price="{{ number_format((float) $price, 2) }}" data-record-description="{{ $record->description }}" data-record-note="{{ $type === 'packages' ? '' : $record->admin_note }}" data-record-visibility="{{ $visible ? 'Published' : 'Not published' }}" data-record-created="{{ $record->created_at?->format('d M Y') }}" data-review-url="{{ $type === 'studios' ? route('admin.catalog.studios.review', $record) : ($type === 'services' ? route('admin.catalog.services.review', $record) : route('admin.catalog.packages.review', $record)) }}">
                    <td><div class="mgmt-name-cell"><span class="mgmt-record-icon"><i class="bi bi-{{ $type === 'studios' ? 'camera' : ($type === 'services' ? 'layers' : 'box-seam') }}"></i></span><div><strong>{{ $title }}</strong><small>#{{ $record->id }} · {{ $secondary ?: '—' }}</small></div></div></td>
                    <td>{{ $host ?? '—' }}</td>
                    <td>{{ $type === 'studios' ? ($record->category?->name ?? '—') : ($type === 'services' ? ($record->specialty ?: '—') : ucfirst($record->type)) }}</td>
                    <td><strong class="mgmt-number">{{ number_format((float) $price, 2) }} JOD</strong></td>
                    <td><span class="sp-badge {{ in_array($statusValue, ['approved', 'active'], true) ? 'sp-badge-success' : ($statusValue === 'rejected' ? 'sp-badge-danger' : 'sp-badge-warning') }}">{{ ucfirst($statusValue) }}</span></td>
                    <td class="mgmt-actions"><button type="button" class="sp-icon-button" data-mgmt-action="catalog-view" title="View" aria-label="View {{ $title }}"><i class="bi bi-eye"></i></button><button type="button" class="sp-icon-button" data-mgmt-menu="mgmt-catalog-{{ $type }}-{{ $record->id }}" title="More" aria-label="More about {{ $title }}"><i class="bi bi-three-dots-vertical"></i></button>
                        <div class="mgmt-menu" id="mgmt-catalog-{{ $type }}-{{ $record->id }}" popover="auto"><button type="button" class="mgmt-menu-item" data-mgmt-action="catalog-view"><i class="bi bi-eye"></i>View details</button>@if($type === 'packages' || $record->status === 'pending')<button type="button" class="mgmt-menu-item" data-mgmt-action="catalog-review"><i class="bi bi-patch-check"></i>Review status</button>@endif</div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="mgmt-empty">No {{ $type }} match your filters.</td></tr>
            @endforelse
        </x-admin.data-table>
    </section>
</div>
<dialog id="mgmt-catalog-dialog" class="mgmt-dialog mgmt-detail-dialog" aria-labelledby="mgmt-catalog-title">
    <div class="mgmt-dialog-head"><div><h2 id="mgmt-catalog-title">Listing details</h2><p id="mgmt-catalog-kind">Studio / Service / Package</p></div><button type="button" class="sp-icon-button" data-mgmt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
    <div class="mgmt-dialog-body">
        <div class="mgmt-detail-hero"><div class="mgmt-detail-avatar"><i class="bi bi-camera"></i></div><div><h3 id="mgmt-catalog-name"></h3><p id="mgmt-catalog-owner"></p><span class="sp-badge" id="mgmt-catalog-status"></span></div></div>
        <div class="mgmt-detail-grid"><div><span>Location / category</span><strong id="mgmt-catalog-secondary"></strong></div><div><span>Price</span><strong id="mgmt-catalog-price"></strong></div><div><span>Public visibility</span><strong id="mgmt-catalog-visibility"></strong></div><div><span>Created</span><strong id="mgmt-catalog-created"></strong></div></div>
        <div class="mgmt-detail-text"><span>Description</span><p id="mgmt-catalog-description"></p></div>
        <form id="mgmt-catalog-review-form" class="mgmt-inline-review" method="POST" hidden>
            @csrf
            @method('PATCH')
            <div class="mgmt-review-grid"><div class="mgmt-field"><label for="mgmt-catalog-new-status">Review status</label><select class="sp-select" id="mgmt-catalog-new-status" name="status" required><option value="approved">Approve</option><option value="rejected">Reject</option><option value="inactive" hidden>Disable / Unpublish</option></select></div><div class="mgmt-field"><label for="mgmt-catalog-note">Admin note</label><textarea id="mgmt-catalog-note" name="admin_note" maxlength="255" rows="2" placeholder="Reason or moderation note"></textarea></div></div>
            <p class="mgmt-hint">Packages use is_active directly; approving publishes and disabling unpublishes. Studios and services use their existing review status.</p>
            <div class="mgmt-form-alert" data-mgmt-errors hidden role="alert"></div>
            <div class="mgmt-review-actions"><button type="button" class="sp-btn sp-btn-secondary" data-mgmt-review-cancel>Cancel</button><button type="submit" class="sp-btn sp-btn-primary" data-mgmt-submit>Save review</button></div>
        </form>
        <p class="mgmt-hint" id="mgmt-catalog-readonly" hidden>Studio and service review actions are limited to pending listings. Packages can also be unpublished.</p>
    </div>
    <div class="mgmt-dialog-footer"><button type="button" class="sp-btn sp-btn-secondary" data-mgmt-close>Close</button><button type="button" class="sp-btn sp-btn-primary" id="mgmt-catalog-review-trigger">Review status</button></div>
</dialog>
@endsection
@push('scripts')
<script src="{{ asset('js/admin/management.js') }}" defer></script>
@endpush
