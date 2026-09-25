@extends('layouts.auth')
@section('content')
<div class="auth-card">
    <header class="auth-intro">
        @include('auth.partials.brand')
        <p class="subtitle">Đăng nhập quản trị & nhân viên</p>
    </header>
    @include('auth.partials.form-status')
    <form action="{{ route('internalauth.login') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
        @csrf
        <div class="form-group text-start">
            <label for="username" class="form-label">Tên đăng nhập</label>
            <input class="form-control @error('username') is-invalid @enderror" id="username" name="username" value="{{ old('username') }}" required autocomplete="username" data-validate aria-describedby="username-error" placeholder="Tên đăng nhập nội bộ">
            <p class="field-error" id="username-error" data-error-for="username">@error('username'){{ $message }}@enderror</p>
        </div>
        <div class="form-group text-start">
            <label for="password" class="form-label">Mật khẩu</label>
            <div class="password-wrapper">
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="current-password" data-validate aria-describedby="password-error" placeholder="Nhập mật khẩu">
                <button type="button" class="toggle-password" aria-label="Hiện mật khẩu"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
            <p class="field-error" id="password-error" data-error-for="password">@error('password'){{ $message }}@enderror</p>
        </div>
        <button type="submit" class="btn btn-auth">Đăng nhập hệ thống</button>
        <p class="text-center mt-4 mb-0 small"><a href="{{ route('login') }}">Đăng nhập khách hàng</a></p>
    </form>
</div>
@endsection
