@extends('layouts.auth')

@section('content')
<div class="sana-auth-card">
    <header class="sana-heading auth-intro">
        <h1 class="sana-heading__title">Login System</h1>
    </header>

    @include('auth.partials.form-status')

    <form action="{{ route('internalauth.login') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
        @csrf

        {{-- Username for internal system --}}
        <div class="sana-form-group">
            <input type="text"
                   class="sana-input @error('username') is-invalid @enderror"
                   id="username"
                   name="username"
                   value="{{ old('username') }}"
                   required
                   autocomplete="username"
                   data-validate
                   data-required-message="Vui lòng nhập tên đăng nhập."
                   aria-describedby="username-error"
                   placeholder="Tên đăng nhập hệ thống">
            <p class="field-error" id="username-error" data-error-for="username">
                @error('username')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror
            </p>
        </div>

        {{-- Password for internal system --}}
        <div class="sana-form-group">
            <div class="sana-password-wrap">
                <input type="password"
                       class="sana-input @error('password') is-invalid @enderror"
                       id="password"
                       name="password"
                       required
                       autocomplete="current-password"
                       data-validate
                       data-required-message="Vui lòng nhập mật khẩu."
                       aria-describedby="password-error"
                       placeholder="Mật khẩu hệ thống">
                <button type="button" class="sana-password-toggle" aria-label="Hiện mật khẩu">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            <p class="field-error" id="password-error" data-error-for="password">
                @error('password')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror
            </p>
        </div>

        {{-- Submit Button --}}
        <button type="submit" class="sana-submit-btn">Login System</button>

        {{-- Return to Customer Login --}}
        <div class="sana-switch-link" style="margin-top: 20px;">
            <span>Bạn là khách lưu trú?</span>
            <a href="{{ route('login') }}">Đăng nhập khách hàng</a>
        </div>
    </form>
</div>
@endsection
