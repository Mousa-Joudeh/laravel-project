@extends('layouts.admin')
@section('title', 'Categories')
@push('styles')
<link rel="stylesheet" href="{{ asset('css/admin/data-table.css') }}">
<link rel="stylesheet" href="{{ asset('css/admin/management.css') }}">
@endpush
@section('content')
<div class="admin-page-heading">
    <div><span class="admin-kicker">MANAGEMENT / CATEGORIES</span><h1>Categories</h1><p>Control how studios are organized across SPACE.</p></div>
    <button type="button" class="sp-btn sp-btn-primary" data-mgmt-action="category-create"><i class="bi bi-plus-lg"></i> Add category</button>
</div>
<div class="mgmt-page" data-management="categories">
    @if(session('success'))<div class="sp-badge sp-badge-success" role="status">{{ session('success') }}</div>@endif
    @if($errors->has('file'))<div class="mgmt-form-alert" role="alert">{{ $errors->first('file') }}</div>@endif
    <section class="sp-card mgmt-card">
        @include('admin.partials.management-tabs', ['tabs' => ['' => ['All', $counts['all']], 'active' => ['Active', $counts['active']], 'inactive' => ['Inactive', $counts['inactive']]], 'selected' => $status, 'route' => 'admin.categories.index', 'param' => 'status'])
        <div class="mgmt-toolbar">
            <form method="GET" action="{{ route('admin.categories.index') }}" class="mgmt-filters">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="hidden" name="sort" value="{{ $sort }}">
                <input type="hidden" name="direction" value="{{ $direction }}">
                <input type="hidden" name="per_page" value="{{ $categories->perPage() }}">
                <label class="mgmt-search"><i class="bi bi-search"></i><input type="search" name="search" value="{{ $search }}" placeholder="Search categories..." aria-label="Search categories"></label>
                <button type="submit" class="sp-btn sp-btn-primary">Search</button>
                <a href="{{ route('admin.categories.index') }}" class="sp-btn sp-btn-secondary">Reset</a>
            </form>
            <button type="button" class="sp-icon-button" data-mgmt-menu="mgmt-category-tools" aria-label="Table options"><i class="bi bi-three-dots-vertical"></i></button>
            <div id="mgmt-category-tools" popover="auto" class="mgmt-menu" role="menu"><button type="button" class="mgmt-menu-item" data-mgmt-print><i class="bi bi-printer"></i>Print table</button><a class="mgmt-menu-item" href="{{ route('admin.csv.export', array_merge(request()->except('page'), ['section' => 'categories'])) }}" role="menuitem"><i class="bi bi-download"></i>Export CSV</a><button type="button" class="mgmt-menu-item" data-admin-csv-import-open><i class="bi bi-upload"></i>Import CSV</button></div>
        </div>
        <x-admin.data-table :records="$categories" id="category-table">
            <x-slot:head>
                <tr>
                    <th><x-admin.sort-link name="name" label="Category" :sort="$sort" :direction="$direction" /></th>
                    <th><x-admin.sort-link name="studios_count" label="Studios" :sort="$sort" :direction="$direction" /></th>
                    <th><x-admin.sort-link name="is_active" label="Visibility" :sort="$sort" :direction="$direction" /></th>
                    <th><x-admin.sort-link name="created_at" label="Created" :sort="$sort" :direction="$direction" /></th>
                    <th class="mgmt-action-head">Actions</th>
                </tr>
            </x-slot:head>
            @forelse($categories as $category)
                <tr data-record-id="{{ $category->id }}" data-record-name="{{ $category->name }}" data-record-img="{{ $category->img }}" data-record-status="{{ $category->is_active ? 'active' : 'inactive' }}" data-update-url="{{ route('admin.categories.update', $category) }}" data-delete-url="{{ route('admin.categories.destroy', $category) }}" data-record-count="{{ $category->studios_count }}">
                    <td><div class="mgmt-name-cell"><span class="mgmt-record-icon"><i class="bi bi-grid"></i></span><div><strong>{{ $category->name }}</strong><small>{{ $category->slug }}</small></div></div></td>
                    <td><span class="mgmt-number">{{ number_format($category->studios_count) }}</span></td>
                    <td><span class="sp-badge {{ $category->is_active ? 'sp-badge-success' : '' }}">{{ $category->is_active ? 'Active' : 'Inactive' }}</span></td>
                    <td>{{ $category->created_at?->format('d M Y') ?? '—' }}</td>
                    <td class="mgmt-actions"><button class="sp-icon-button" type="button" data-mgmt-action="category-edit" title="Edit category" aria-label="Edit {{ $category->name }}"><i class="bi bi-pencil-fill"></i></button>
                        <button class="sp-icon-button" type="button" data-mgmt-menu="mgmt-cat-{{ $category->id }}" title="More options" aria-label="More options for {{ $category->name }}"><i class="bi bi-three-dots-vertical"></i></button>
                        <div class="mgmt-menu" popover="auto" id="mgmt-cat-{{ $category->id }}" role="menu">
                            <button type="button" class="mgmt-menu-item" data-mgmt-action="category-edit"><i class="bi bi-pencil"></i>Edit</button>
                            <button type="button" class="mgmt-menu-item is-danger" data-mgmt-action="category-delete"><i class="bi bi-trash3"></i>Delete</button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="mgmt-empty">No categories match your filters.</td></tr>
            @endforelse
        </x-admin.data-table>
    </section>
</div>
<dialog id="mgmt-category-dialog" class="mgmt-dialog" aria-labelledby="mgmt-category-title">
    <form id="mgmt-category-form" action="{{ route('admin.categories.store') }}" method="POST">
        @csrf
        <input type="hidden" name="_method" id="mgmt-category-method" value="POST">
        <div class="mgmt-dialog-head"><div><h2 id="mgmt-category-title">Add category</h2><p>Category information</p></div><button class="sp-icon-button" type="button" data-mgmt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
        <div class="mgmt-dialog-body">
            <div class="mgmt-form-alert" data-mgmt-errors hidden role="alert"></div>
            <div class="mgmt-field"><label for="mgmt-category-name">Name *</label><input class="sp-input" id="mgmt-category-name" name="name" maxlength="100" required placeholder="e.g. Photography studios"></div>
            <div class="mgmt-field"><label for="mgmt-category-img">Image URL / path (optional)</label><input class="sp-input" id="mgmt-category-img" name="img" maxlength="500" placeholder="https://... or images/category.png"></div>
            <label class="mgmt-checkbox"><input type="checkbox" id="mgmt-category-active" checked>Active category</label>
            <input type="hidden" name="is_active" id="mgmt-category-status" value="1">
            <p class="mgmt-hint">Inactive categories are hidden from category choices that filter for active records. Existing studios remain assigned.</p>
        </div>
        <div class="mgmt-dialog-footer"><button type="button" class="sp-btn sp-btn-secondary" data-mgmt-close>Cancel</button><button type="submit" class="sp-btn sp-btn-primary" data-mgmt-submit>Save category</button></div>
    </form>
</dialog>
<dialog id="mgmt-category-delete-dialog" class="mgmt-dialog mgmt-confirm" aria-labelledby="mgmt-delete-title">
    <div class="mgmt-dialog-head"><div><h2 id="mgmt-delete-title">Delete category?</h2><p>This action removes the category from active listings.</p></div><button class="sp-icon-button" type="button" data-mgmt-close aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
    <div class="mgmt-dialog-body"><div class="mgmt-danger-icon"><i class="bi bi-trash3"></i></div><p>Delete <strong id="mgmt-delete-name"></strong>?</p><p class="mgmt-hint">If studios use this category, deletion will be blocked. Deactivate it instead.</p><div class="mgmt-form-alert" data-mgmt-errors hidden role="alert"></div></div>
    <div class="mgmt-dialog-footer"><button type="button" class="sp-btn sp-btn-secondary" data-mgmt-close>Cancel</button><button type="button" class="sp-btn sp-btn-danger" id="mgmt-category-confirm-delete">Delete category</button></div>
</dialog>
@include('admin._workspace.partials.category-import')
@endsection
@push('scripts')
<script src="{{ asset('js/admin/management.js') }}" defer></script>
<script src="{{ asset('js/admin/csv-tools.js') }}" defer></script>
@endpush
