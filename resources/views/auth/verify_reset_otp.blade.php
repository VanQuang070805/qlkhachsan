@extends('layouts.auth')

@section('content')
<div class="auth-card" data-aos="fade-up" data-aos-duration="1000">
    <div class="hotel-brand">Royal Hotel</div>
    <p class="subtitle">Xác Nhận Khôi Phục Mật Khẩu</p>
    <p class="text-center mb-4" style="color: #475569;">Mã OTP đã được gửi đến email:<br><strong>{{ $email }}</strong></p>

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

    <form action="{{ route('password.verify-otp') }}" method="POST">
        @csrf
        <div class="mb-4 text-start">
            <label for="code" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Nhập Mã OTP (6 chữ số)</label>
            <input type="text" class="form-control" style="text-align: center; letter-spacing: 2px; font-weight: 600;" id="code" name="otp" required maxlength="6" placeholder="Ví dụ: 123456" autocomplete="off">
        </div>
        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-primary">Xác Nhận OTP</button>
        </div>
        
        <div class="text-center">
            <span class="text-muted" style="font-size: 0.9rem;">Không nhận được mã?</span><br>
        </div>
    </form>
    
    <div class="text-center">
        <a href="{{ route('password.resend-otp') }}" class="btn btn-link p-0 text-decoration-none mt-1" style="color: #b08d28; font-weight: 500;">
            Gửi lại mã OTP
        </a>
    </div>

    <div class="text-center mt-3">
        <a href="{{ route('password.forgot') }}" style="font-size: 0.85rem; color: #64748b; text-decoration: none;">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>
</div>

@endsection
