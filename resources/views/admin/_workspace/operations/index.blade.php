@extends('layouts.admin')
@php
    $headings = ['bookings' => ['Bookings', 'Reservations and scheduling records', 'calendar-check'], 'transactions' => ['Transactions', 'Payment and refund records', 'credit-card'], 'reviews' => ['Reviews', 'Customer feedback and moderation', 'star']];
    $heading = $headings[$type];
    $base = 'admin.operations.' . $type;
@endphp
@section('title', $heading[0])
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/data-table.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/management.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/operations.css') }}">
@endpush
@section('content')
<div class="admin-page-heading"><div><span class="admin-kicker">OPERATIONS / {{ strtoupper($type) }}</span><h1>{{ $heading[0] }}</h1><p>{{ $heading[1] }}. {{ $type === 'reviews' ? 'Inappropriate reviews can be removed.' : 'All records are read-only for administrators.' }}</p></div></div>
<div class="mgmt-page" data-management="operations">
    <section class="sp-card mgmt-card">
        <div class="mgmt-toolbar">
            <form class="mgmt-filters" method="GET" action="{{ route($base) }}">
                <input type="hidden" name="sort" value="{{ $sort }}"><input type="hidden" name="direction" value="{{ $direction }}"><input type="hidden" name="per_page" value="{{ $records->perPage() }}">
                <label class="mgmt-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $search }}" placeholder="Search {{ $type }}..." aria-label="Search records"></label>
                @if($type === 'bookings')
                    <select class="sp-select" name="status" aria-label="Booking status"><option value="">All statuses</option>@foreach(['pending_payment' => 'Pending payment', 'confirmed' => 'Confirmed', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'expired' => 'Expired'] as $code => $label)<option value="{{ $code }}" @selected($status === $code)>{{ $label }}</option>@endforeach</select>
                @elseif($type === 'transactions')
                    <select class="sp-select" name="status" aria-label="Transaction status"><option value="">All statuses</option>@foreach(['pending' => 'Pending', 'processing' => 'Processing', 'succeeded' => 'Succeeded', 'failed' => 'Failed', 'cancelled' => 'Cancelled'] as $code => $label)<option value="{{ $code }}" @selected($status === $code)>{{ $label }}</option>@endforeach</select>
                    <select class="sp-select" name="transaction_type" aria-label="Transaction type"><option value="">All types</option><option value="payment" @selected($typeFilter === 'payment')>Payment</option><option value="refund" @selected($typeFilter === 'refund')>Refund</option></select>
                @else
                    <select class="sp-select" name="rating" aria-label="Review rating"><option value="0">All ratings</option>@foreach([5, 4, 3, 2, 1] as $stars)<option value="{{ $stars }}" @selected($rating === $stars)>{{ $stars }} stars</option>@endforeach</select>
                @endif
                <button class="sp-btn sp-btn-primary" type="submit">Apply</button><a class="sp-btn sp-btn-secondary" href="{{ route($base) }}">Reset</a>
            </form>
            <button type="button" class="sp-icon-button" data-mgmt-menu="operation-tools" aria-label="Table options"><i class="bi bi-three-dots-vertical"></i></button>
            <div class="mgmt-menu" id="operation-tools" popover="auto"><button class="mgmt-menu-item" data-mgmt-print type="button"><i class="bi bi-printer"></i>Print table</button><a class="mgmt-menu-item" href="{{ route('admin.csv.export', array_merge(request()->except('page'), ['section' => ($type)])) }}" role="menuitem"><i class="bi bi-download"></i>Export CSV</a></div>
        </div>
        <x-admin.data-table :records="$records" id="{{ $type }}-table">
            <x-slot:head>
                @if($type === 'bookings')
                    <tr><th>Reference</th><th>Customer</th><th>Studio</th><th><x-admin.sort-link name="starts_at" label="Scheduled" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="total_price" label="Amount" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="status" label="Status" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr>
                @elseif($type === 'transactions')
                    <tr><th>Transaction</th><th>Booking</th><th>Type</th><th><x-admin.sort-link name="amount" label="Amount" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="status" label="Status" :sort="$sort" :direction="$direction" /></th><th><x-admin.sort-link name="processed_at" label="Processed" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr>
                @else
                    <tr><th>Customer</th><th>Studio</th><th><x-admin.sort-link name="rating" label="Rating" :sort="$sort" :direction="$direction" /></th><th>Comment</th><th><x-admin.sort-link name="created_at" label="Posted" :sort="$sort" :direction="$direction" /></th><th class="mgmt-action-head">Actions</th></tr>
                @endif
            </x-slot:head>
            @forelse($records as $record)
                @if($type === 'bookings')
                    <tr data-record-id="{{ $record->id }}" data-record-type="booking" data-record-name="{{ $record->reference }}" data-record-owner="{{ $record->customer?->name }}" data-record-studio="{{ $record->studio?->name }}" data-record-email="{{ $record->customer?->email }}" data-record-status="{{ $record->status }}" data-record-price="{{ number_format((float) $record->total_price, 3) }} {{ $record->currency }}" data-record-period="{{ $record->starts_at?->format('d M Y, H:i') }} – {{ $record->ends_at?->format('d M Y, H:i') }}" data-record-extra="{{ $record->items->pluck('item_name')->implode(', ') }}">
                        <td><strong class="mgmt-number">{{ $record->reference }}</strong></td><td>{{ $record->customer?->name ?? '—' }}</td><td>{{ $record->studio?->name ?? '—' }}</td><td>{{ $record->starts_at?->format('d M Y, H:i') }}</td><td><strong class="mgmt-number">{{ number_format((float) $record->total_price, 2) }} {{ $record->currency }}</strong></td><td><span class="sp-badge {{ $record->status === 'completed' || $record->status === 'confirmed' ? 'sp-badge-success' : (in_array($record->status, ['cancelled','expired']) ? 'sp-badge-danger' : 'sp-badge-warning') }}">{{ str_replace('_', ' ', ucfirst($record->status)) }}</span></td>
                        <td class="mgmt-actions"><button type="button" class="sp-icon-button" data-work-action="operation-view" aria-label="View booking"><i class="bi bi-eye"></i></button><button type="button" class="sp-icon-button" data-mgmt-menu="booking-{{ $record->id }}" aria-label="More actions"><i class="bi bi-three-dots-vertical"></i></button><div class="mgmt-menu" id="booking-{{ $record->id }}" popover="auto"><button class="mgmt-menu-item" data-work-action="operation-view" type="button"><i class="bi bi-eye"></i>View details</button></div></td>
                    </tr>
                @elseif($type === 'transactions')
                    <tr data-record-id="{{ $record->id }}" data-record-type="transaction" data-record-name="Transaction #{{ $record->id }}" data-record-owner="{{ $record->booking?->customer?->name }}" data-record-studio="{{ $record->booking?->studio?->name }}" data-record-email="{{ $record->provider_reference }}" data-record-status="{{ $record->status }}" data-record-price="{{ number_format((float) $record->amount, 3) }} {{ $record->currency }}" data-record-period="{{ $record->processed_at?->format('d M Y, H:i') ?? 'Not processed' }}" data-record-extra="{{ $record->type }} · {{ $record->provider }} · {{ $record->booking?->reference ?? 'No booking' }} · {{ $record->reason ?? 'No reason' }}">
                        <td><strong class="mgmt-number">#{{ $record->id }}</strong><small class="workspace-subline">{{ $record->provider }}</small></td><td>{{ $record->booking?->reference ?? '—' }}</td><td>{{ ucfirst($record->type) }}</td><td><strong class="mgmt-number">{{ number_format((float) $record->amount, 2) }} {{ $record->currency }}</strong></td><td><span class="sp-badge {{ $record->status === 'succeeded' ? 'sp-badge-success' : (in_array($record->status, ['failed','cancelled']) ? 'sp-badge-danger' : 'sp-badge-warning') }}">{{ ucfirst($record->status) }}</span></td><td>{{ $record->processed_at?->format('d M Y') ?? '—' }}</td><td class="mgmt-actions"><button type="button" class="sp-icon-button" data-work-action="operation-view" aria-label="View transaction"><i class="bi bi-eye"></i></button><button type="button" class="sp-icon-button" data-mgmt-menu="tx-{{ $record->id }}" aria-label="More actions"><i class="bi bi-three-dots-vertical"></i></button><div class="mgmt-menu" id="tx-{{ $record->id }}" popover="auto"><button type="button" class="mgmt-menu-item" data-work-action="operation-view"><i class="bi bi-eye"></i>View details</button></div></td>
                    </tr>
                @else
                    <tr data-record-id="{{ $record->booking_id }}" data-record-type="review" data-record-name="Review for {{ $record->booking?->reference }}" data-record-owner="{{ $record->booking?->customer?->name }}" data-record-studio="{{ $record->booking?->studio?->name }}" data-record-email="{{ $record->booking?->customer?->email }}" data-record-status="{{ $record->rating }} / 5" data-record-price="{{ $record->booking?->reference }}" data-record-period="{{ $record->created_at?->format('d M Y, H:i') }}" data-record-extra="{{ $record->comment }}" data-remove-url="{{ route('admin.operations.reviews.remove', $record) }}">
                        <td><strong>{{ $record->booking?->customer?->name ?? '—' }}</strong><small class="workspace-subline">{{ $record->booking?->reference }}</small></td><td>{{ $record->booking?->studio?->name ?? '—' }}</td><td><span class="workspace-stars">{{ str_repeat('★', $record->rating) }}{{ str_repeat('☆', 5 - $record->rating) }}</span></td><td class="workspace-comment">{{ \Illuminate\Support\Str::limit($record->comment ?? 'No comment', 70) }}</td><td>{{ $record->created_at?->format('d M Y') }}</td><td class="mgmt-actions"><button type="button" class="sp-icon-button" data-work-action="operation-view" aria-label="View review"><i class="bi bi-eye"></i></button><button type="button" class="sp-icon-button" data-mgmt-menu="review-{{ $record->booking_id }}" aria-label="More actions"><i class="bi bi-three-dots-vertical"></i></button><div class="mgmt-menu" id="review-{{ $record->booking_id }}" popover="auto"><button type="button" class="mgmt-menu-item" data-work-action="operation-view"><i class="bi bi-eye"></i>View details</button><button type="button" class="mgmt-menu-item workspace-danger" data-work-action="review-delete"><i class="bi bi-trash"></i>Remove review</button></div></td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="{{ $type === 'reviews' ? 6 : 7 }}" class="mgmt-empty">No {{ $type }} match your filters.</td></tr>
            @endforelse
        </x-admin.data-table>
    </section>
</div>
<dialog id="operation-detail" class="mgmt-dialog workspace-dialog" aria-labelledby="operation-title">
    <div class="mgmt-dialog-head"><div><h2 id="operation-title">Record details</h2><p id="operation-type">Read-only record</p></div><button type="button" class="sp-icon-button" data-mgmt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
    <div class="mgmt-dialog-body">
        <div class="mgmt-detail-grid"><div><span>Customer</span><strong id="operation-owner"></strong></div><div><span>Studio</span><strong id="operation-studio"></strong></div><div><span>Email / Provider reference</span><strong id="operation-email"></strong></div><div><span>Status / Rating</span><strong id="operation-status"></strong></div><div><span>Amount / Booking</span><strong id="operation-price"></strong></div><div><span>Date / Schedule</span><strong id="operation-period"></strong></div></div>
        <div class="mgmt-detail-text"><span>Items / Details / Comment</span><p id="operation-extra" class="workspace-preserve"></p></div>
    </div>
    <div class="mgmt-dialog-footer"><button type="button" class="sp-btn sp-btn-secondary" data-mgmt-close>Close</button></div>
</dialog>
@if($type === 'reviews')
<dialog id="review-remove-dialog" class="mgmt-dialog workspace-dialog" aria-labelledby="review-remove-title">
    <div class="mgmt-dialog-head"><div><h2 id="review-remove-title">Remove review</h2><p>Content moderation</p></div><button type="button" class="sp-icon-button" data-mgmt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
    <div class="mgmt-dialog-body">
        <p class="workspace-instruction">This will hide the review from public listings using soft delete. It does not modify the booking or its payment.</p>
        <form id="review-remove-form">@csrf<input type="hidden" name="_method" value="DELETE"><div class="mgmt-field"><label for="review-remove-reason">Reason for removal</label><textarea id="review-remove-reason" name="reason" required minlength="5" maxlength="255" rows="3" placeholder="State which moderation rule was violated"></textarea></div><div class="mgmt-form-alert" data-mgmt-errors hidden role="alert"></div><div class="mgmt-review-actions"><button class="sp-btn sp-btn-danger" type="submit">Confirm removal</button></div></form>
    </div>
    <div class="mgmt-dialog-footer"><button type="button" class="sp-btn sp-btn-secondary" data-mgmt-close>Cancel</button></div>
</dialog>
@endif
@endsection
@push('scripts')
<script src="{{ asset('js/admin/management.js') }}" defer></script>
<script src="{{ asset('js/admin/workspace.js') }}" defer></script>
@endpush
