@extends('layouts.auth')

@section('content')
<div class="auth-card" data-aos="fade-up" data-aos-duration="1000">
    <div class="hotel-brand">Royal Hotel</div>
    <p class="subtitle">Tạo mật khẩu mới</p>

    <form action="{{ route('password.reset') }}" method="POST" autocomplete="off" novalidate>
    @csrf
        <div class="mb-4 text-start">
            <label for="new_password" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Mật khẩu mới</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    class="form-control"
                    id="new_password"
                    name="new_password"
                    placeholder="Nhập mật khẩu mới..."
                    required
                    autocomplete="new-password"
                    style="padding-left: 15px;"
                >
                <button type="button" class="toggle-password" aria-label="Hiện/Ẩn mật khẩu">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>
        
        <div class="mb-4 text-start">
            <label for="confirm_password" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Xác nhận mật khẩu</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    class="form-control"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Nhập lại mật khẩu..."
                    required
                    autocomplete="new-password"
                    style="padding-left: 15px;"
                >
                <button type="button" class="toggle-password" aria-label="Hiện/Ẩn mật khẩu">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-primary">Lưu Mật Khẩu</button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}" style="color: #b08d28; font-weight: 600; text-decoration: none;">Quay lại Đăng Nhập</a>
        </div>
    </form>
</div>

@endsection
