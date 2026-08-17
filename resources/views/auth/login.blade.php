@extends('layouts.auth')

@section('content')
<div class="auth-card" data-aos="fade-up" data-aos-duration="1000">
    <div class="hotel-brand">Royal Hotel</div>
    <p class="subtitle">Khách sạn & Khu nghỉ dưỡng sang trọng</p>

    @if(session('error'))
        <div class="alert alert-danger text-center mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success text-center mb-4">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
        </div>
    @endif

    {{-- ===== ĐĂNG NHẬP KHÁCH HÀNG (Mặc định) ===== --}}
    <div id="customer-card">
        <form action="{{ route('login') }}" method="POST" autocomplete="on" novalidate>
            @csrf
            <input type="hidden" name="role" value="customer">
            
            <div class="mb-4 text-start">
                <label for="email" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Email</label>
                <input
                    type="email"
                    class="form-control"
                    id="email"
                    name="email"
                    placeholder="Nhập email của bạn..."
                    required
                    value="{{ old('email') }}"
                    autocomplete="email"
                    inputmode="email"
                    style="padding-left: 15px;"
                >
            </div>
            
            <div class="mb-4 text-start">
                <label for="password" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Mật khẩu</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        class="form-control"
                        id="password"
                        name="password"
                        placeholder="Nhập mật khẩu..."
                        required
                        autocomplete="current-password"
                        style="padding-left: 15px;"
                    >
                    <button type="button" class="toggle-password" aria-label="Hiện/Ẩn mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                <div class="text-end mt-2">
                    <a href="{{ route('password.forgot') }}" style="font-size: 0.85rem; color: #64748b;">Quên mật khẩu?</a>
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary">Đăng Nhập</button>
            </div>

            <div class="divider">HOẶC</div>

            <div class="text-center">
                Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a>
            </div>
            
            <div class="text-center mt-3">
                <a href="{{ route('home') }}" style="font-size: 0.85rem; color: #64748b; text-decoration: none;">
                    <i class="bi bi-arrow-left"></i> Quay lại Trang chủ
                </a>
            </div>
        </form>
        <!-- Link sang đăng nhập nhân viên -->
        <p class="mt-4 text-center" style="font-size: 0.85rem; color: #64748b;">
            Bạn là nhân viên khách sạn? <a href="#" id="show-staff-link" style="color: #b08d28; text-decoration: none; font-weight: 600;">Đăng nhập tại đây</a>
        </p>
    </div>

    {{-- ===== ĐĂNG NHẬP NHÂN VIÊN (Ẩn mặc định) ===== --}}
    <div id="staff-card" class="d-none">
        <form action="{{ route('login') }}" method="POST" autocomplete="on" novalidate>
            @csrf
            <input type="hidden" name="role" value="staff">
            
            <div class="mb-4 text-start">
                <label for="staff-username" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Tên đăng nhập (Username)</label>
                <input
                    type="text"
                    class="form-control"
                    id="staff-username"
                    name="username"
                    placeholder="Nhập tên đăng nhập nhân viên..."
                    required
                    value="{{ old('username') }}"
                    autocomplete="username"
                    style="padding-left: 15px;"
                >
            </div>
            
            <div class="mb-4 text-start">
                <label for="staff-password" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Mật khẩu</label>
                <div class="password-wrapper">
                    <input
                        type="password"
                        class="form-control"
                        id="staff-password"
                        name="password"
                        placeholder="Nhập mật khẩu..."
                        required
                        autocomplete="current-password"
                        style="padding-left: 15px;"
                    >
                    <button type="button" class="toggle-password" aria-label="Hiện/Ẩn mật khẩu">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <div class="d-grid mb-3">
                <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #e9cd2e, #e87d0c); box-shadow: 0 4px 15px rgba(29, 78, 216, 0.3);">
                    Đăng Nhập Hệ Thống
                </button>
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('home') }}" style="font-size: 0.85rem; color: #64748b; text-decoration: none;">
                    <i class="bi bi-arrow-left"></i> Quay lại Trang chủ
                </a>
            </div>
        </form>
        <!-- Link về đăng nhập khách hàng -->
        <p class="mt-4 text-center" style="font-size: 0.85rem; color: #64748b;">
            Bạn là khách đặt phòng? <a href="#" id="show-customer-link" style="color: #b08d28; text-decoration: none; font-weight: 600;">Đăng nhập khách hàng</a>
        </p>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const customerCard = document.getElementById('customer-card');
        const staffCard    = document.getElementById('staff-card');
        const showStaffLink = document.getElementById('show-staff-link');
        const showCustomerLink = document.getElementById('show-customer-link');

        // Khôi phục trạng thái form nếu có lỗi nhập liệu (cũ) của nhân viên hoặc khách hàng
        const hasStaffError = "{{ old('username') }}" !== "";
        if (hasStaffError) {
            customerCard.classList.add('d-none');
            staffCard.classList.remove('d-none');
        }

        showStaffLink.addEventListener('click', function(e) {
            e.preventDefault();
            customerCard.classList.add('d-none');
            staffCard.classList.remove('d-none');
        });

        showCustomerLink.addEventListener('click', function(e) {
            e.preventDefault();
            staffCard.classList.add('d-none');
            customerCard.classList.remove('d-none');
        });
    });
</script>
@endsection
