<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title','Quản trị · Rosaliza Hotel')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    @vite(['resources/css/internal.css','resources/js/internal.js']) @stack('styles')
</head>
<body class="internal-shell" data-theme="light">
@php($isAdmin=true)
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
<div class="workspace-shell">
<aside class="workspace-sidebar" id="workspaceSidebar">
    <div class="sidebar-macos-dots" style="display: flex; gap: 7px; padding: 18px 18px 12px;" aria-hidden="true">
        <span class="ctrl-dot ctrl-red"></span>
        <span class="ctrl-dot ctrl-yellow"></span>
        <span class="ctrl-dot ctrl-green"></span>
    </div>
    <nav class="sidebar-nav">
        <div class="sidebar-section-label">BÁO CÁO &amp; PHÂN TÍCH</div>
        <a class="{{ request()->routeIs('admin.dashboard','admin.reports')?'is-active':'' }}" href="{{ route('admin.reports') }}"><i class="bi bi-grid-1x2"></i><span>Tổng quan</span></a>
        
        <div class="sidebar-section-label">VẬN HÀNH KHÁCH SẠN</div>
        <a class="{{ request()->routeIs('staff.bookings')?'is-active':'' }}" href="{{ route('staff.bookings') }}"><i class="bi bi-calendar2-check"></i><span>Sơ đồ phòng</span></a>
        <a class="{{ request()->routeIs('admin.rooms.*','admin.room-types.*')?'is-active':'' }}" href="{{ route('admin.rooms.index') }}"><i class="bi bi-door-open"></i><span>Phòng &amp; hạng phòng</span></a>
        <a class="{{ request()->routeIs('staff.iot','staff.face-id.*')?'is-active':'' }}" href="{{ route('staff.iot') }}"><i class="bi bi-person-bounding-box"></i><span>Face ID &amp; IoT</span></a>
        <a class="{{ request()->routeIs('staff.cancellations')?'is-active':'' }}" href="{{ route('staff.cancellations') }}"><i class="bi bi-arrow-counterclockwise"></i><span>Hủy phòng &amp; hoàn tiền</span></a>
        
        <div class="sidebar-section-label">QUẢN TRỊ HỆ THỐNG</div>
        <a class="{{ request()->routeIs('admin.users.*')?'is-active':'' }}" href="{{ route('admin.users.index') }}"><i class="bi bi-people"></i><span>Người dùng</span></a>
        <a class="{{ request()->routeIs('admin.roles.*')?'is-active':'' }}" href="{{ route('admin.roles.index') }}"><i class="bi bi-shield-check"></i><span>Phân quyền</span></a>
        <a class="{{ request()->routeIs('admin.price-settings.*')?'is-active':'' }}" href="{{ route('admin.price-settings.index') }}"><i class="bi bi-sliders"></i><span>Điều chỉnh giá</span></a>
        <a class="{{ request()->routeIs('receptionist.profile')?'is-active':'' }}" href="{{ route('receptionist.profile') }}"><i class="bi bi-person"></i><span>Hồ sơ cá nhân</span></a>
    </nav>
    @include('layouts.partials.internal-sidebar-footer')
</aside>
<section class="workspace-content">
    @include('layouts.partials.internal-topbar')
    <main class="internal-main">
        @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
        @yield('content')
    </main>
</section>
</div>
@include('layouts.partials.internal-tools')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>@stack('scripts')
</body></html>
