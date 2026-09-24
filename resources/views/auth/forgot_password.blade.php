@extends('layouts.auth')

@section('content')
<div class="auth-card" data-aos="fade-up" data-aos-duration="1000">
    <div class="hotel-brand">Royal Hotel</div>
    <p class="subtitle">Khôi phục mật khẩu tài khoản</p>

    <form action="{{ route('password.forgot') }}" method="POST" autocomplete="on" novalidate>
    @csrf
        <div class="mb-4 text-start">
            <label for="email" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Email của bạn</label>
            <input
                type="email"
                class="form-control"
                id="email"
                name="email"
                placeholder="Nhập email đã đăng ký..."
                required
                autocomplete="email"
                inputmode="email"
                style="padding-left: 15px;"
            >
        </div>
        
        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-primary">Gửi Yêu Cầu Đặt Lại</button>
        </div>
        
        <div class="text-center">
            <a href="{{ route('login') }}" style="color: #b08d28; font-weight: 600; text-decoration: none;">Quay lại Đăng Nhập</a>
        </div>
    </form>
</div>

@endsection
