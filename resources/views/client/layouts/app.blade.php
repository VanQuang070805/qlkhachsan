<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="Rosaliza Hotel - Không gian nghỉ dưỡng riêng tư và thư thái.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Rosaliza Hotel')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@1&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="client-shell">
    <div class="scroll-progress" data-scroll-progress aria-hidden="true"></div>
    @include('client.partials.header')
    <main id="main-content" tabindex="-1">
        @include('client.partials.flash')
        @yield('content')
    </main>
    @include('client.partials.footer')
    @include('client.partials.chatbot')
    @include('client.partials.ios-tab-bar')
    @stack('scripts')
</body>
</html>
