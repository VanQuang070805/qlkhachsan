@extends('layouts.auth')

@section('content')
<div class="sana-auth-card">
    <header class="sana-heading auth-intro" style="margin-bottom: 12px !important; text-align: center; align-items: center;">
        <h1 class="sana-heading__title" style="text-align: center;">Xác thực tài khoản</h1>
    </header>

    {{-- Email Pill (Centered) --}}
    <div style="display: flex; justify-content: center; width: 100%;">
        <div class="otp-badge">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <span>Mã đã gửi đến: <strong>{{ $email }}</strong></span>
        </div>
    </div>

    @if(session('success'))
        <div data-animated-flash="success" role="status">{{ session('success') }}</div>
    @endif

    {{-- Main OTP Verification Form (Centered) --}}
    <form action="{{ route('verify') }}" method="POST" id="otp-form" novalidate style="display: flex; flex-direction: column; align-items: center; width: 100%;">
        @csrf
        <input type="hidden" name="otp" id="otp-hidden" value="{{ old('otp') }}">

        {{-- CodeSlots Component from React Bits --}}
        <div class="code-slots" data-code-slots role="group" aria-label="Mã OTP 6 chữ số">
            <div class="code-slots__row" id="otp-grid" data-status="{{ ($errors->has('otp') || session('error')) ? 'error' : 'idle' }}">
                <input type="text"
                       inputmode="numeric"
                       pattern="[0-9]*"
                       maxlength="6"
                       class="code-slots__input"
                       id="otp-main-input"
                       autocomplete="one-time-code"
                       aria-label="Mã OTP 6 chữ số"
                       aria-describedby="otp-error"
                       @if($errors->has('otp') || session('error')) aria-invalid="true" @endif
                       autofocus>
                @for ($i = 0; $i < 6; $i++)
                    <span class="code-slots__slot" data-slot-index="{{ $i }}">
                        <span class="code-slots__fill"></span>
                        <span class="code-slots__digit"></span>
                    </span>
                @endfor
                <span class="code-slots__wash" aria-hidden="true">
                    <span class="code-slots__check">
                        <i class="bi bi-check2"></i>
                    </span>
                </span>
                <span class="code-slots__caret" aria-hidden="true">
                    <span class="code-slots__caret-line"></span>
                </span>
            </div>
        </div>

        @php
            $initialError = null;
            if (isset($errors) && $errors->has('otp')) {
                $initialError = $errors->first('otp');
            } elseif (session('error')) {
                $initialError = session('error');
            }
        @endphp

        {{-- Red Error Text: Only red text, centered under OTP boxes --}}
        <div class="otp-error-text" id="otp-error" data-error-for="otp" role="alert" @if(!$initialError) data-empty="true" style="display: none !important;" @endif>
            @if($initialError)
                <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                <span>{{ $initialError }}</span>
            @endif
        </div>

        {{-- Submit Button (Centered directly under OTP boxes) --}}
        <button type="submit" class="sana-submit-btn otp-submit-btn" id="btn-submit-otp">
            <i class="bi bi-shield-check" aria-hidden="true"></i>
            <span>Xác thực ngay</span>
        </button>
    </form>

    {{-- Resend OTP Section (Centered under OTP boxes) --}}
    <div class="otp-action-row">
        <span>Không nhận được mã?</span>
        <button type="button" class="otp-resend-btn" id="btn-resend-otp">Gửi lại mã</button>
        <span class="otp-countdown-text" id="otp-countdown" style="display: none;">(gửi lại sau <strong id="countdown-sec">60</strong>s)</span>
    </div>

    {{-- Hidden form for resend CSRF --}}
    <form id="resend-form" action="{{ route('verify.resend') }}" method="POST" style="display: none;">
        @csrf
    </form>

    {{-- Switch / Back Link (Centered under OTP boxes) --}}
    <div class="sana-switch-link otp-switch-link">
        <span>Sử dụng tài khoản khác?</span>
        <a href="{{ route('login') }}">Đăng nhập</a>
    </div>
</div>

@include('auth.partials.otp-script', ['storageKey' => 'last_verify_otp_sent_time'])
@endsection
