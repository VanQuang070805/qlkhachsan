<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $pageTitle ?? 'Royal Hotel')</title>
    <meta name="description" content="Royal Hotel — không gian lưu trú riêng tư, được chăm chút cho từng nhịp nghỉ.">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('royal-hotel-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="client-shell">

@include('client.partials.page-loader')

@include('client.partials.header')

<!-- FLASH MESSAGE -->
@include('client.partials.flash')

<!-- NỘI DUNG CHÍNH -->
<main class="legacy-main" id="main-content" tabindex="-1">
    @yield('content')
</main>

<!-- FOOTER -->
@include('client.partials.footer')

@include('client.partials.chatbot')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>
