@extends('layouts.auth')

@section('content')
<div class="auth-card" data-aos="fade-up" data-aos-duration="1000">
    <div class="hotel-brand">Royal Hotel</div>
    <p class="subtitle" style="color: #64748b; font-weight: 500; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 1px;">
        Đăng Nhập Nội Bộ Nhân Viên
    </p>

    <form action="{{ url('/internalauth/login') }}" method="POST" autocomplete="on" novalidate>
        @csrf
        
        <div class="mb-4 text-start">
            <label for="username" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Tên đăng nhập (Username)</label>
            <input
                type="text"
                class="form-control"
                id="username"
                name="username"
                placeholder="Nhập tên đăng nhập nội bộ..."
                required
                value="{{ old('username') }}"
                autocomplete="username"
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
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #0f172a, #334155); box-shadow: 0 4px 15px rgba(15, 23, 42, 0.4); border: none;">
                Đăng Nhập Hệ Thống
            </button>
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('login') }}" style="font-size: 0.85rem; color: #6303f4; text-decoration: none; font-weight: 600;">
                <i class="bi bi-arrow-left"></i> Quay lại Đăng nhập Khách hàng
            </a>
        </div>
    </form>
</div>
@endsection
