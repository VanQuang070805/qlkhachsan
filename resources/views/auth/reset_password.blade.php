@extends('layouts.auth')

@section('content')
<div class="auth-card">
    @include('auth.partials.brand')
    <p class="subtitle">Tạo mật khẩu mới</p>

    <form action="{{ route('password.reset') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
    @csrf
        <div class="mb-4 text-start">
            <label for="new_password" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Mật khẩu mới</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Nhập mật khẩu mới..."
                    required
                    autocomplete="new-password"
                    minlength="8"
                    data-validate
                    data-required-message="Vui lòng nhập mật khẩu mới."
                    data-min-message="Mật khẩu cần có ít nhất 8 ký tự."
                    aria-describedby="password-error"
                >
                <button type="button" class="toggle-password" aria-label="Hiện/Ẩn mật khẩu">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <p class="field-error" id="password-error" data-error-for="password">@error('password')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror</p>
        </div>
        
        <div class="mb-4 text-start">
            <label for="confirm_password" class="form-label" style="font-weight: 600; color: #1e293b; font-size: 0.9rem; margin-bottom: 8px;">Xác nhận mật khẩu</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    class="form-control"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Nhập lại mật khẩu..."
                    required
                    autocomplete="new-password"
                    minlength="8"
                    data-validate
                    data-required-message="Vui lòng xác nhận mật khẩu."
                    aria-describedby="password_confirmation-error"
                >
                <button type="button" class="toggle-password" aria-label="Hiện/Ẩn mật khẩu">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <p class="field-error" id="password_confirmation-error" data-error-for="password_confirmation"></p>
        </div>

        <div class="d-grid mb-4">
            <button type="submit" class="btn btn-primary">Lưu Mật Khẩu</button>
        </div>

        <div class="text-center">
            <a href="{{ route('login') }}">Quay lại Đăng Nhập</a>
        </div>
    </form>
</div>

@endsection
