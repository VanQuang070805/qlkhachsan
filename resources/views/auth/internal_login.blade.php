@extends('layouts.auth')

@php
    $activePortal = $portal ?? (request()->is('admin*') ? 'admin' : 'staff');
    $actionUrl = $formAction ?? ($activePortal === 'admin' ? route('admin.login.submit') : route('staff.login.submit'));
    $headingTitle = $portalTitle ?? ($activePortal === 'admin' ? 'Cổng Quản Trị' : 'Cổng Nhân Viên');
    $headingSubtitle = $portalSubtitle ?? ($activePortal === 'admin'
        ? 'Đăng nhập hệ thống quản trị doanh thu, hạng phòng và phân quyền.'
        : 'Đăng nhập hệ thống vận hành lễ tân, sơ đồ phòng và Face ID.');
@endphp

@section('content')
<div class="sana-auth-card">
    <header class="sana-heading auth-intro">
        <h1 class="sana-heading__title">{{ $headingTitle }}</h1>
        <p style="margin: 6px 0 0; font-size: 13px; color: #52525b;">{{ $headingSubtitle }}</p>
    </header>

    @include('auth.partials.form-status')

    <form action="{{ $actionUrl }}" method="POST" autocomplete="on" novalidate data-inline-validation>
        @csrf

        {{-- Username for internal system --}}
        <div class="sana-form-group">
            <label class="sana-field-label" for="username">Tên đăng nhập <span class="sana-required-mark" aria-hidden="true">*</span></label>
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
            <label class="sana-field-label" for="password">Mật khẩu <span class="sana-required-mark" aria-hidden="true">*</span></label>
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
        <button type="submit" class="sana-submit-btn">
            {{ $activePortal === 'admin' ? 'Đăng nhập Quản trị' : 'Đăng nhập Nhân viên' }}
        </button>

        <p class="sana-terms" style="margin-top: 16px;">
            {{ $activePortal === 'admin'
                ? 'Khu vực dành riêng cho tài khoản Quản trị viên Rosaliza Hotel.'
                : 'Khu vực dành riêng cho tài khoản Lễ tân và Nhân viên vận hành Rosaliza Hotel.' }}
        </p>
    </form>
</div>
@endsection
