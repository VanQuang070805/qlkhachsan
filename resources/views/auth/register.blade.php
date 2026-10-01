@extends('layouts.auth')

@section('content')
<div class="sana-auth-card">
    <header class="sana-heading auth-intro">
        <h1 class="sana-heading__title">Create your Royal account</h1>
        <p class="sana-heading__sub">Begin your quiet luxury journey</p>
    </header>

    @include('auth.partials.form-status')

    <form action="{{ route('register') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
        @csrf

        {{-- Google OAuth Button --}}
        <a class="sana-social-btn" href="{{ route('auth.google') }}" data-no-transition>
            <svg viewBox="0 0 18 18" aria-hidden="true">
                <path fill="#4285F4" d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.797 2.716v2.259h2.909c1.702-1.567 2.684-3.875 2.684-6.615Z"/>
                <path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.956-2.18l-2.909-2.259c-.806.54-1.835.859-3.047.859-2.344 0-4.328-1.585-5.037-3.714H.956v2.332A9 9 0 0 0 9 18Z"/>
                <path fill="#FBBC05" d="M3.963 10.706A5.41 5.41 0 0 1 3.682 9c0-.592.102-1.168.281-1.706V4.962H.956A9 9 0 0 0 0 9c0 1.452.347 2.827.956 4.038l3.007-2.332Z"/>
                <path fill="#EA4335" d="M9 3.58c1.321 0 2.507.454 3.441 1.346l2.581-2.581C13.464.892 11.426 0 9 0A9 9 0 0 0 .956 4.962l3.007 2.332C4.672 5.165 6.656 3.58 9 3.58Z"/>
            </svg>
            <span>Sign up with Google</span>
        </a>

        <div class="sana-divider"><span>or</span></div>

        {{-- Full Name --}}
        <div class="sana-form-group">
            <input type="text"
                   class="sana-input @error('name') is-invalid @enderror"
                   id="name"
                   name="name"
                   placeholder="Họ và tên của bạn"
                   value="{{ old('name') }}"
                   autocomplete="name"
                   required
                   minlength="2"
                   data-validate
                   data-required-message="Vui lòng nhập họ và tên."
                   aria-describedby="name-error">
            <p class="field-error" id="name-error" data-error-for="name">
                @error('name')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror
            </p>
        </div>

        {{-- Email --}}
        <div class="sana-form-group">
            <input type="email"
                   class="sana-input @error('email') is-invalid @enderror"
                   id="email"
                   name="email"
                   placeholder="Địa chỉ email"
                   value="{{ old('email') }}"
                   autocomplete="email"
                   inputmode="email"
                   required
                   data-validate
                   data-required-message="Vui lòng nhập email."
                   data-type-message="Email chưa đúng định dạng."
                   aria-describedby="email-error">
            <p class="field-error" id="email-error" data-error-for="email">
                @error('email')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror
            </p>
        </div>

        {{-- Phone --}}
        <div class="sana-form-group">
            <input type="tel"
                   class="sana-input @error('phone') is-invalid @enderror"
                   id="phone"
                   name="phone"
                   placeholder="Số điện thoại (10 chữ số)"
                   value="{{ old('phone') }}"
                   autocomplete="tel"
                   inputmode="numeric"
                   pattern="0[0-9]{9}"
                   maxlength="10"
                   required
                   data-validate
                   data-required-message="Vui lòng nhập số điện thoại."
                   data-pattern-message="Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0."
                   aria-describedby="phone-error">
            <p class="field-error" id="phone-error" data-error-for="phone">
                @error('phone')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror
            </p>
        </div>

        {{-- Password --}}
        <div class="sana-form-group">
            <div class="sana-password-wrap">
                <input type="password"
                       class="sana-input @error('password') is-invalid @enderror"
                       id="password"
                       name="password"
                       placeholder="Ít nhất 10 ký tự, có chữ hoa, chữ thường và số"
                       autocomplete="new-password"
                       minlength="10"
                       pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).{10,}"
                       required
                       data-validate
                       data-required-message="Vui lòng tạo mật khẩu."
                       data-min-message="Mật khẩu cần có ít nhất 10 ký tự."
                       data-pattern-message="Mật khẩu phải có chữ hoa, chữ thường và số."
                       aria-describedby="password-error">
                <button type="button" class="sana-password-toggle" aria-label="Hiện mật khẩu">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            <p class="field-error" id="password-error" data-error-for="password">
                @error('password')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror
            </p>
        </div>

        {{-- Confirm Password --}}
        <div class="sana-form-group">
            <div class="sana-password-wrap">
                <input type="password"
                       class="sana-input"
                       id="password_confirmation"
                       name="password_confirmation"
                       placeholder="Xác nhận lại mật khẩu"
                       autocomplete="new-password"
                       minlength="10"
                       required
                       data-validate
                       data-required-message="Vui lòng xác nhận mật khẩu."
                       aria-describedby="password_confirmation-error">
                <button type="button" class="sana-password-toggle" aria-label="Hiện mật khẩu">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            <p class="field-error" id="password_confirmation-error" data-error-for="password_confirmation"></p>
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="sana-submit-btn">Tạo tài khoản</button>

        {{-- Terms Notice --}}
        <p class="sana-terms">
            Bằng cách tạo tài khoản, bạn đồng ý với <a href="{{ route('contact') }}">Điều khoản dịch vụ</a> và <a href="{{ route('contact') }}">Chính sách bảo mật</a> của Royal Hotel.
        </p>

        <div class="sana-switch-link" style="margin-top: 14px;">
            <span>Đã có tài khoản?</span>
            <a href="{{ route('login') }}">Đăng nhập</a>
        </div>
    </form>
</div>
@endsection
