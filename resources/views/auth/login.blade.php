@extends('layouts.auth')

@section('content')
<div class="sana-auth-card">
    <div id="customer-card">
        <header class="sana-heading auth-intro">
            <h1 class="sana-heading__title">Welcome to Rosaliza Hotel</h1>
        </header>

        @if(session('success'))
            <p class="auth-form-status" role="status" style="margin-bottom: 16px; padding: 10px 18px; border-radius: 999px; background: rgba(34,197,94,0.1); color: #166534; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                <i class="bi bi-check-circle" aria-hidden="true"></i><span>{{ session('success') }}</span>
            </p>
        @endif

        <form action="{{ route('login') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
            @csrf
            <input type="hidden" name="role" value="customer">

            {{-- Google OAuth Button --}}
            <a class="sana-social-btn" href="{{ route('auth.google') }}" data-no-transition>
                <svg viewBox="0 0 18 18" aria-hidden="true">
                    <path fill="#4285F4" d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.797 2.716v2.259h2.909c1.702-1.567 2.684-3.875 2.684-6.615Z"/>
                    <path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.956-2.18l-2.909-2.259c-.806.54-1.835.859-3.047.859-2.344 0-4.328-1.585-5.037-3.714H.956v2.332A9 9 0 0 0 9 18Z"/>
                    <path fill="#FBBC05" d="M3.963 10.706A5.41 5.41 0 0 1 3.682 9c0-.592.102-1.168.281-1.706V4.962H.956A9 9 0 0 0 0 9c0 1.452.347 2.827.956 4.038l3.007-2.332Z"/>
                    <path fill="#EA4335" d="M9 3.58c1.321 0 2.507.454 3.441 1.346l2.581-2.581C13.464.892 11.426 0 9 0A9 9 0 0 0 .956 4.962l3.007 2.332C4.672 5.165 6.656 3.58 9 3.58Z"/>
                </svg>
                <span>Continue with Google</span>
            </a>

            <div class="sana-divider"><span>or</span></div>

            {{-- Email Input (Full Pill) --}}
            <div class="sana-form-group">
                <label class="sana-field-label" for="email">Email <span class="sana-required-mark" aria-hidden="true">*</span></label>
                <input type="email"
                       class="sana-input @if($errors->has('email') || session('error')) is-invalid @endif"
                       id="email"
                       name="email"
                       placeholder="janesmith1.mobbin@gmail.com"
                       required
                       value="{{ old('email', request()->cookie('royal_remembered_email')) }}"
                       autocomplete="username"
                       inputmode="email"
                       data-validate
                       data-required-message="Vui lòng nhập email."
                       data-type-message="Email chưa đúng định dạng."
                       aria-describedby="email-error">
                <p class="field-error" id="email-error" data-error-for="email">
                    @if ($errors->has('email'))
                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $errors->first('email') }}
                    @elseif (session('error'))
                        <i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ session('error') }}
                    @endif
                </p>
            </div>

            {{-- Password Input (Full Pill with Toggle) --}}
            <div class="sana-form-group">
                <label class="sana-field-label" for="password">Mật khẩu <span class="sana-required-mark" aria-hidden="true">*</span></label>
                <div class="sana-password-wrap">
                    <input type="password"
                           class="sana-input @error('password') is-invalid @enderror"
                           id="password"
                           name="password"
                           placeholder="Enter your password"
                           required
                           autocomplete="current-password"
                           data-validate
                           data-required-message="Vui lòng nhập mật khẩu."
                           aria-describedby="password-error">
                    <button type="button" class="sana-password-toggle" aria-label="Hiện mật khẩu">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="field-error" id="password-error" data-error-for="password">
                    @error('password')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror
                </p>
            </div>

            {{-- Options: Remember & Forgot --}}
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 4px 12px 18px; font-size: 13px; color: #71717a;">
                <label style="display: flex; align-items: center; gap: 7px; cursor: pointer;">
                    <input type="checkbox" name="remember" value="1" @checked(old('remember', true)) style="accent-color: #000000; border-radius: 4px;">
                    <span>Duy trì đăng nhập</span>
                </label>
                <a href="{{ route('password.forgot') }}" style="color: #52525b; text-decoration: underline;">Quên mật khẩu?</a>
            </div>

            {{-- Submit Button --}}
            <button type="submit" class="sana-submit-btn">Continue</button>

            {{-- Terms / Fine Print --}}
            <p class="sana-terms">
                By signing in, you agree to the <a href="{{ route('contact') }}">Terms of Use</a>, <a href="{{ route('contact') }}">Privacy Notice</a>, and Cookie Policy.
            </p>

            <div class="sana-switch-link" style="margin-top: 14px;">
                <span>Chưa có tài khoản?</span>
                <a href="{{ route('register') }}">Đăng ký ngay</a>
            </div>

            <div class="sana-switch-link" style="margin-top: 8px;">
                <span>Quản trị &amp; Nhân viên?</span>
                <a href="{{ route('internalauth.login') }}">Đăng nhập hệ thống</a>
            </div>
        </form>
    </div>
</div>
@endsection
