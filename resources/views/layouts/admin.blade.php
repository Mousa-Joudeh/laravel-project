<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Overview') · SPACE Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin/global.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-body">
<div class="admin-shell">
    @include('admin.partials.sidebar')
    <button type="button" class="admin-backdrop" id="admin-backdrop" aria-label="Close navigation"></button>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="admin-topbar-left">
                <button type="button" class="admin-menu-button" id="admin-menu-button" aria-controls="admin-sidebar" aria-expanded="false" aria-label="Open navigation">
                    <i class="bi bi-list"></i>
                </button>
                <div class="admin-breadcrumb">SPACE <span>/</span> Administration <span>/</span> <strong>@yield('title', 'Overview')</strong></div>
            </div>
            <div class="admin-topbar-right">
                <span class="admin-date">{{ now()->format('d M Y') }}</span>
                <span class="admin-avatar">{{ strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</span>
            </div>
        </header>

        <main class="admin-content">
            @yield('content')
        </main>
    </div>
</div>
<script src="{{ asset('js/admin.js') }}" defer></script>
@stack('scripts')
</body>
</html>
