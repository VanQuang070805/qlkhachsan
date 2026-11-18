@extends('layouts.auth')

@section('content')
<div class="sana-auth-card">
    <header class="sana-heading auth-intro">
        <h1 class="sana-heading__title">Khôi phục mật khẩu</h1>
        <p class="sana-heading__sub">Nhập email tài khoản để nhận mã xác thực OTP</p>
    </header>

    @include('auth.partials.form-status')

    <form action="{{ route('password.forgot') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
        @csrf
        <div class="sana-form-group">
            <label class="sana-field-label" for="email">Địa chỉ email <span class="sana-required-mark" aria-hidden="true">*</span></label>
            <input
                type="email"
                class="sana-input @error('email') is-invalid @enderror"
                id="email"
                name="email"
                placeholder="tenban@email.com"
                required
                autocomplete="email"
                inputmode="email"
                data-validate
                data-required-message="Vui lòng nhập địa chỉ email."
                data-type-message="Email chưa đúng định dạng."
                aria-describedby="email-error"
                value="{{ old('email') }}"
            >
            <p class="field-error" id="email-error" data-error-for="email">
                @error('email')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror
            </p>
        </div>
        
        <button type="submit" class="sana-submit-btn">Gửi yêu cầu đặt lại</button>
        
        <div class="sana-switch-link" style="margin-top: 20px;">
            <span>Nhớ lại mật khẩu?</span>
            <a href="{{ route('login') }}">Quay lại đăng nhập</a>
        </div>
    </form>
</div>
@endsection
