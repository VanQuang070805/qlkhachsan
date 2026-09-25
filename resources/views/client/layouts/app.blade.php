<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Royal Hotel - Không gian nghỉ dưỡng riêng tư và thư thái.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Royal Hotel')</title>
    <link rel="icon" href="{{ asset('royal-hotel-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    @stack('scripts')
</body>
</html>
