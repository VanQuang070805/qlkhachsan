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
        <div style="display: flex; justify-content: center; width: 100%;">
            <div class="otp-status-toast is-success" role="status" style="margin-bottom: 14px; max-width: 360px; width: 100%;">
                <i class="bi bi-check-circle" aria-hidden="true"></i>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- Toast feedback for AJAX resend --}}
    <div id="otp-toast" class="otp-status-toast" style="display: none; max-width: 360px; margin-left: auto; margin-right: auto;" role="status"></div>

    {{-- Main OTP Verification Form (Centered) --}}
    <form action="{{ route('verify') }}" method="POST" id="otp-form" novalidate style="display: flex; flex-direction: column; align-items: center; width: 100%;">
        @csrf
        <input type="hidden" name="otp" id="otp-hidden" value="{{ old('otp') }}">

        {{-- 6-Box Visual Grid (Moved higher up) --}}
        <div class="otp-box-grid" id="otp-grid" role="group" aria-label="Mã OTP 6 chữ số">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-cell" data-otp-index="0" autocomplete="one-time-code" aria-label="Chữ số 1" autofocus>
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-cell" data-otp-index="1" aria-label="Chữ số 2">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-cell" data-otp-index="2" aria-label="Chữ số 3">
            <span class="otp-cell-separator" aria-hidden="true">&ndash;</span>
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-cell" data-otp-index="3" aria-label="Chữ số 4">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-cell" data-otp-index="4" aria-label="Chữ số 5">
            <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="otp-cell" data-otp-index="5" aria-label="Chữ số 6">
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
