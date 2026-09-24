@extends('layouts.auth')

@section('content')
<div class="auth-card register-card" data-aos="fade-up" data-aos-duration="1000">
    <div class="hotel-brand">Royal Hotel</div>
    <p class="subtitle">Trở thành thành viên thân thiết</p>

    <form action="{{ route('register') }}" method="POST">
    @csrf
        <div class="mb-3 text-start">
            <label for="name" class="form-label fw-bold" style="font-size: 0.9rem; color: #1e293b;">Họ và Tên</label>
            <input type="text" class="form-control" style="padding-left: 15px;" id="name" name="name" placeholder="Ví dụ: Nguyễn Văn A" value="{{ old('name') }}" required>
        </div>
        <div class="mb-3 text-start">
            <label for="email" class="form-label fw-bold" style="font-size: 0.9rem; color: #1e293b;">Email</label>
            <input type="email" class="form-control" style="padding-left: 15px;" id="email" name="email" placeholder="email@example.com" value="{{ old('email') }}" required>
        </div>
        <div class="mb-3 text-start">
            <label for="phone" class="form-label fw-bold" style="font-size: 0.9rem; color: #1e293b;">Số điện thoại</label>
            <input type="tel" class="form-control" style="padding-left: 15px;" id="phone" name="phone" placeholder="0912345678" pattern="0[0-9]{9,10}" maxlength="11" value="{{ old('phone') }}" required>
        </div>
        <div class="mb-4 text-start">
            <label for="password" class="form-label fw-bold" style="font-size: 0.9rem; color: #1e293b;">Mật Khẩu</label>
            <input type="password" class="form-control" style="padding-left: 15px;" id="password" name="password" placeholder="Tạo mật khẩu mạnh..." required>
        </div>
        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-auth" style="background: linear-gradient(135deg, #d4af37, #b08d28); text-transform: uppercase; letter-spacing: 1px;">Tạo Tài Khoản</button>
        </div>
        
        <div class="d-flex align-items-center text-muted my-4" style="font-weight: 500;">
            <div style="flex: 1; border-bottom: 1px solid #cbd5e1;"></div>
            <div style="margin: 0 15px;">HOẶC</div>
            <div style="flex: 1; border-bottom: 1px solid #cbd5e1;"></div>
        </div>

        <div class="text-center">
            Đã có tài khoản? <a href="{{ route('login') }}" style="color: #b08d28; font-weight: 600; text-decoration: none;">Đăng nhập</a>
        </div>
    </form>
</div>

@endsection
