@extends('layouts.admin')

@section('title', 'Users')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/users.css') }}">
@endpush

@section('content')
@php
    $search = $search ?? '';
    $filterRole = $filterRole ?? '';
    $status = $status ?? '';
    $sort = $sort ?? 'created_at';
    $direction = $direction ?? 'desc';
    $counts = $counts ?? ['all' => $users->total(), 'active' => null, 'suspended' => null];
    $columns = [
        'name' => 'User',
        'phone' => 'Phone',
        'company' => 'Company',
        'city' => 'City',
        'role' => 'Role',
        'status' => 'Status',
        'created_at' => 'Joined',
    ];
@endphp

<div class="admin-page-heading">
    <div>
        <span class="admin-kicker">MANAGEMENT / USERS</span>
        <h1>Users</h1>
        <p>Manage platform accounts and access.</p>
    </div>
    <button type="button" id="sp-open-create-user" class="sp-btn sp-btn-primary">
        <i class="bi bi-plus-lg"></i> Add user
    </button>
</div>

@if(session('success'))
    <div class="sp-alert sp-alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="sp-alert sp-alert-danger" role="alert">{{ $errors->first() }}</div>
@endif

<section class="sp-card sp-users-card" id="sp-users-card" data-admin-users-url="{{ route('admin.users.index') }}" data-current-user-id="{{ auth()->id() }}">
    <div class="sp-filter-tabs">
        @foreach([
            '' => ['All', $counts['all']],
            'active' => ['Active', $counts['active']],
            'suspended' => ['Suspended', $counts['suspended']],
        ] as $value => $tab)
            <a
                class="sp-filter-tab {{ $status === $value ? 'is-active' : '' }}"
                href="{{ route('admin.users.index', array_merge(
                    request()->except('page', 'status'),
                    $value === '' ? [] : ['status' => $value]
                )) }}"
            >
                {{ $tab[0] }}
                @if($tab[1] !== null)
                    <span class="sp-filter-count">{{ $tab[1] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="sp-users-toolbar">
        <form method="GET" action="{{ route('admin.users.index') }}" class="sp-filter-toolbar">
            <input type="hidden" name="status" value="{{ $status }}">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="direction" value="{{ $direction }}">
            <input type="hidden" name="per_page" value="{{ $users->perPage() }}">

            <select name="role" class="sp-select sp-role-filter" aria-label="Filter role">
                <option value="">All roles</option>
                <option value="admin" @selected($filterRole === 'admin')>Admin</option>
                <option value="provider" @selected($filterRole === 'provider')>Provider</option>
                <option value="customer" @selected($filterRole === 'customer')>Customer</option>
            </select>

            <div class="sp-search-field">
                <i class="bi bi-search" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ $search }}" class="sp-input"
                       placeholder="Search name, email, phone or company..."
                       aria-label="Search users">
            </div>

            <button type="submit" class="sp-btn sp-btn-primary">Search</button>
            <a href="{{ route('admin.users.index') }}" class="sp-btn sp-btn-secondary">Reset</a>
        </form>

        <button type="button" class="sp-icon-button" data-sp-menu="sp-table-tools"
                title="Table options" aria-label="Table options" aria-haspopup="menu">
            <i class="bi bi-three-dots-vertical"></i>
        </button>
        <div class="sp-popup-menu" id="sp-table-tools" popover="auto" role="menu">
            <button type="button" class="sp-popup-item" data-sp-print role="menuitem">
                <i class="bi bi-printer"></i> Print
            </button>
            @if(Route::has('admin.users.export'))
                <a class="sp-popup-item"
                   href="{{ route('admin.users.export', request()->query()) }}" role="menuitem">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            @endif
            @if(Route::has('admin.users.import'))
                <button type="button" class="sp-popup-item" data-sp-import-open role="menuitem">
                    <i class="bi bi-upload"></i> Import CSV
                </button>
            @endif
        </div>
    </div>

    <div class="sp-table-wrap">
        <table class="sp-table sp-users-table">
            <thead>
                <tr>
                    @foreach($columns as $key => $label)
                        <th scope="col">
                            <a class="sp-sort-link {{ $sort === $key ? 'is-active' : '' }}"
                               href="{{ route('admin.users.index', array_merge(
                                   request()->except('page'),
                                   ['sort' => $key,
                                    'direction' => $sort === $key && $direction === 'asc' ? 'desc' : 'asc']
                               )) }}">
                                {{ $label }}
                                <i class="bi {{ $sort === $key
                                    ? ($direction === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down')
                                    : 'bi-arrow-down-up' }}" aria-hidden="true"></i>
                            </a>
                        </th>
                    @endforeach
                    <th scope="col" class="sp-action-column">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr
                        data-user-id="{{ $user->id }}"
                        data-user-name="{{ $user->name }}"
                        data-user-email="{{ $user->email }}"
                        data-user-phone="{{ $user->phone }}"
                        data-user-city="{{ $user->city }}"
                        data-user-company="{{ $user->providerProfile?->company_name }}"
                        data-user-role="{{ $user->role }}"
                        data-user-status="{{ $user->status }}"
                        data-user-verified="{{ $user->email_verified_at ? 'Verified' : 'Not verified' }}"
                        data-user-joined="{{ $user->created_at?->format('d M Y') }}"
                        data-user-updated="{{ $user->updated_at?->format('d M Y') }}"
                        data-user-update-url="{{ route('users.update', $user) }}"
                        data-user-delete-url="{{ route('users.destroy', $user) }}"
                    >
                        <td>
                            <div class="sp-user-cell">
                                <span class="sp-user-avatar">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                <div class="sp-user-info">
                                    <strong>{{ $user->name }}</strong>
                                    <span>{{ $user->email }}</span>
                                </div>
                            </div>
                        </td>
                        <td>{{ $user->phone ?: '—' }}</td>
                        <td>{{ $user->providerProfile?->company_name ?? '—' }}</td>
                        <td>{{ $user->city ?: '—' }}</td>
                        <td><span class="sp-badge">{{ ucfirst($user->role) }}</span></td>
                        <td>
                            <span class="sp-badge {{ $user->status === 'active' ? 'sp-badge-success' : 'sp-badge-danger' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td>{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                        <td class="sp-action-column">
                            <div class="sp-table-actions">

                                <button
                                    type="button"
                                    class="sp-icon-button"
                                    data-sp-user-action="edit"
                                    title="Edit user"
                                    aria-label="Edit {{ $user->name }}"
                                >
                                    <i class="bi bi-pencil-fill"></i>
                                </button>

                                <div class="sp-more-wrap">
                                    <button
                                        type="button"
                                        class="sp-icon-button sp-more-toggle"
                                        data-more-toggle="sp-user-menu-{{ $user->id }}"
                                        aria-controls="sp-user-menu-{{ $user->id }}"
                                        aria-expanded="false"
                                        title="More actions"
                                        aria-label="More actions for {{ $user->name }}"
                                    >
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>

                                    <div class="sp-more-menu" id="sp-user-menu-{{ $user->id }}" popover="auto" data-more-menu>
                                        <button
                                            type="button"
                                            class="sp-more-item"
                                            data-sp-user-action="view"
                                        >
                                            <i class="bi bi-eye"></i>
                                            <span>View</span>
                                        </button>

                                        <button
                                            type="button"
                                            class="sp-more-item"
                                            data-sp-user-action="edit"
                                        >
                                            <i class="bi bi-pencil"></i>
                                            <span>Edit</span>
                                        </button>

                                        @if(auth()->id() !== $user->id)
                                            <button
                                                type="button"
                                                class="sp-more-item is-danger"
                                                data-sp-user-action="delete"
                                            >
                                                <i class="bi bi-trash"></i>
                                                <span>Delete</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8"><div class="sp-empty">No users found.</div></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sp-list-footer">
        <label class="sp-dense-control">
            <input type="checkbox" id="sp-dense-toggle">
            <span class="sp-switch-track"></span>
            <span>Dense</span>
        </label>
        <div class="sp-footer-right">
            <form method="GET" action="{{ route('admin.users.index') }}" class="sp-per-page-form">
                @foreach(request()->except('page', 'per_page') as $key => $value)
                    @if(is_scalar($value))
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach
                <label for="sp-rows-per-page">Rows per page:</label>
                <select id="sp-rows-per-page" name="per_page" class="sp-per-page-select">
                    @foreach([5, 10, 25, 50] as $size)
                        <option value="{{ $size }}" @selected($users->perPage() === $size)>{{ $size }}</option>
                    @endforeach
                </select>
            </form>
            <span class="sp-range-label">
                {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ $users->total() }}
            </span>
            <div class="sp-footer-pagination">
                @if($users->previousPageUrl())
                    <a href="{{ $users->previousPageUrl() }}" class="sp-icon-button"
                       aria-label="Previous page"><i class="bi bi-chevron-left"></i></a>
                @else
                    <span class="sp-icon-button sp-icon-disabled" aria-hidden="true"><i class="bi bi-chevron-left"></i></span>
                @endif
                @if($users->nextPageUrl())
                    <a href="{{ $users->nextPageUrl() }}" class="sp-icon-button"
                       aria-label="Next page"><i class="bi bi-chevron-right"></i></a>
                @else
                    <span class="sp-icon-button sp-icon-disabled" aria-hidden="true"><i class="bi bi-chevron-right"></i></span>
                @endif
            </div>
        </div>
    </div>
</section>

@if(Route::has('admin.users.import'))
<dialog id="sp-import-dialog" class="sp-import-dialog" aria-labelledby="sp-import-title">
    <div class="sp-import-heading">
        <div><h2 id="sp-import-title">Import users</h2><p>Import customer accounts from a CSV file.</p></div>
        <button type="button" class="sp-icon-button" data-sp-import-close aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    <form method="POST" action="{{ route('admin.users.import') }}" enctype="multipart/form-data"
          class="sp-import-form">
        @csrf
        <label class="sp-label" for="sp-import-file">CSV file</label>
        <input class="sp-input" type="file" name="file" id="sp-import-file"
               accept=".csv,text/csv" required>
        <p class="sp-field-help">Columns: name, email, phone, city. Required: name and email.</p>
        <div class="sp-import-actions">
            <button type="button" class="sp-btn sp-btn-secondary" data-sp-import-close>Cancel</button>
            <button type="submit" class="sp-btn sp-btn-primary">Import</button>
        </div>
    </form>
</dialog>
@endif

@include('admin.partials.create-modal')
@include('admin.partials.user-modal')
@endsection

@push('scripts')
<script src="{{ asset('js/admin/users.js') }}" defer></script>
@endpush
