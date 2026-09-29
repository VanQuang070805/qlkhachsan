@extends('layouts.auth')

@section('content')
<div class="sana-auth-card">
    <header class="sana-heading">
        <h1 class="sana-heading__title">Create new password</h1>
        <p class="sana-heading__sub">Ensure your account stays secure</p>
    </header>

    @include('auth.partials.form-status')

    <form action="{{ route('password.reset') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
        @csrf
        <div class="sana-form-group">
            <div class="sana-password-wrap">
                <input
                    type="password"
                    class="sana-input @error('password') is-invalid @enderror"
                    id="password"
                    name="password"
                    placeholder="Ít nhất 10 ký tự, có chữ hoa, chữ thường và số"
                    required
                    autocomplete="new-password"
                    minlength="10"
                    pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{10,}"
                    data-validate
                    data-required-message="Vui lòng nhập mật khẩu mới."
                    data-min-message="Mật khẩu cần có ít nhất 10 ký tự."
                    data-pattern-message="Mật khẩu phải có chữ hoa, chữ thường và số."
                    aria-describedby="password-error"
                >
                <button type="button" class="sana-password-toggle" aria-label="Hiện mật khẩu">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <p class="field-error" id="password-error" data-error-for="password">@error('password')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror</p>
        </div>
        
        <div class="sana-form-group">
            <div class="sana-password-wrap">
                <input
                    type="password"
                    class="sana-input"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="Xác nhận lại mật khẩu mới"
                    required
                    autocomplete="new-password"
                    minlength="10"
                    data-validate
                    data-required-message="Vui lòng xác nhận mật khẩu."
                    aria-describedby="password_confirmation-error"
                >
                <button type="button" class="sana-password-toggle" aria-label="Hiện mật khẩu">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <p class="field-error" id="password_confirmation-error" data-error-for="password_confirmation"></p>
        </div>

        <button type="submit" class="sana-submit-btn">Lưu mật khẩu mới</button>

        <div class="sana-switch-link" style="margin-top: 20px;">
            <a href="{{ route('login') }}">Quay lại đăng nhập</a>
        </div>
    </form>
</div>
@endsection
