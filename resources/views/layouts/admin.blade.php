<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Quản trị - ROYAL HOTEL')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { font-family: 'DM Sans', sans-serif; background-color: #f8f9fa; display: flex; flex-direction: column; min-height: 100vh; }
        h1, h2, h3, h4, h5, .page-title { font-family: 'Cormorant Garamond', serif; }
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }
        .admin-sidebar { position: sticky; top: 90px; }
        footer { border-top: 1px solid #dee2e6; margin-top: auto !important; }
        main { flex: 1 0 auto; }
    </style>
    @stack('styles')
</head>
<body>

<!-- NAVBAR ADMIN — giao diện giống main nhưng menu riêng -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-building me-1"></i>ROYAL HOTEL
            <span class="badge bg-danger ms-1" style="font-size: 0.65rem; vertical-align: middle;">Admin</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        {{ session('user.fullname', 'Admin') }}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <span class="dropdown-item-text text-muted small">
                                <i class="bi bi-shield-fill-check me-1 text-danger"></i>Quản trị hệ thống
                            </span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('internalauth.logout') }}" method="POST" class="m-0">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-1"></i>Đăng xuất
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- FLASH MESSAGE -->
@if(session('success'))
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif
@if(session('error'))
    <div class="container mt-3">
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
@endif

<!-- NỘI DUNG CHÍNH -->
<main class="py-4">
    <div class="container">
        @yield('content')
    </div>
</main>

<!-- FOOTER -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container">
        <div class="row g-3">
            <div class="col-md-4">
                <h6 class="fw-bold"><i class="bi bi-building me-1"></i>ROYAL HOTEL</h6>
                <p class="text-light small mb-0">Dịch vụ lưu trú cao cấp, phục vụ tận tình 24/7.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Liên hệ</h6>
                <p class="text-light small mb-1"><i class="bi bi-geo-alt me-1"></i>12 Chùa Bộc, Hà Nội</p>
                <p class="text-light small mb-1"><i class="bi bi-telephone me-1"></i>0123 456 789</p>
                <p class="text-light small mb-0"><i class="bi bi-envelope me-1"></i>royalhotel@gmail.com.vn</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Liên kết nhanh</h6>
                <ul class="list-unstyled small">
                    <li><a class="text-light text-decoration-none" href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li><a class="text-light text-decoration-none" href="{{ route('admin.reports') }}">Báo cáo thống kê</a></li>
                    <li><a class="text-light text-decoration-none" href="{{ route('staff.bookings') }}">Quản lý đặt phòng</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary mt-3 mb-2">
        <p class="text-center text-light small mb-0">&copy; {{ date('Y') }} ROYAL HOTEL. All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@stack('scripts')
</body>
</html>