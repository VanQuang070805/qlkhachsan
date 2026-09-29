@extends('layouts.auth')

@section('content')
<div class="sana-auth-card">
    <header class="sana-heading">
        <h1 class="sana-heading__title">Reset your password</h1>
        <p class="sana-heading__sub">Enter your email to receive recovery instructions</p>
    </header>

    @include('auth.partials.form-status')

    <form action="{{ route('password.forgot') }}" method="POST" autocomplete="on" novalidate>
        @csrf
        <div class="sana-form-group">
            <input
                type="email"
                class="sana-input @error('email') is-invalid @enderror"
                id="email"
                name="email"
                placeholder="you@example.com"
                required
                autocomplete="email"
                inputmode="email"
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
