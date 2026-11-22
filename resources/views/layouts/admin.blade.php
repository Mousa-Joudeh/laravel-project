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
    <link rel="stylesheet" href="{{ asset('css/admin/header.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/feedback.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin/ai-floating-chat.css') }}">
    @stack('styles')
</head>
<body class="admin-body">
@inject('adminHeaderAlerts', 'App\Services\Admin\HeaderAlertsService')
@php($headerSummary = $adminHeaderAlerts->summary())
<div class="admin-shell">
    @include('admin.partials.sidebar')
    <button type="button" class="admin-backdrop" id="admin-backdrop" aria-label="Close navigation"></button>

    <div class="admin-main">
        @include('admin.partials.header')

        <main class="admin-content">
            @yield('content')
        </main>
    </div>
</div>
@include('admin.partials.header-panels')
@include('admin.partials.ai-floating-chat')
<script src="{{ asset('js/admin/feedback.js') }}" defer></script>
<script src="{{ asset('js/admin.js') }}" defer></script>
<script src="{{ asset('js/admin/header.js') }}" defer></script>
<script src="{{ asset('js/admin/ai-floating-chat.js') }}" defer></script>
@stack('scripts')
</body>
</html>
