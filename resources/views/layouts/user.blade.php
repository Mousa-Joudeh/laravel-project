<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Book creative studios') · SPACES</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/user.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body>

    @include('partials.user-navbar')

    @include('partials.flash-messages')

    <main class="site-main">
        @yield('content')
    </main>

    @include('partials.user-footer')

    <script>
        
        const menuButton = document.getElementById('user-menu-button');
        const menu = document.getElementById('user-menu');
        if (menuButton) {
            menuButton.addEventListener('click', function (event) {
                event.stopPropagation();
                menu.classList.toggle('open');
            });
            document.addEventListener('click', function () {
                menu.classList.remove('open');
            });
        }

       
        const mobileButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        mobileButton.addEventListener('click', function () {
            mobileMenu.classList.toggle('open');
        });

        
        window.addEventListener('scroll', function () {
            document.querySelector('.site-nav').classList.toggle('scrolled', window.scrollY > 8);
        });
    </script>
    @stack('scripts')
</body>
</html>