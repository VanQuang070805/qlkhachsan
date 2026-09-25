@extends('layouts.auth')

@section('content')
<div class="auth-card register-card">
    <header class="auth-intro">
        @include('auth.partials.brand')
        <p class="subtitle">Tạo tài khoản cho hành trình sắp tới</p>
    </header>
    @include('auth.partials.form-status')

    <form action="{{ route('register') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
        @csrf
        @foreach([
            ['name','text','Họ và tên','Nguyễn Văn A','name'],
            ['email','email','Email','you@example.com','email'],
            ['phone','tel','Số điện thoại','0912345678','tel'],
        ] as [$name,$type,$label,$placeholder,$autocomplete])
        <div class="form-group text-start">
            <label for="{{ $name }}" class="form-label">{{ $label }}</label>
            <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" class="form-control @error($name) is-invalid @enderror" value="{{ old($name) }}" placeholder="{{ $placeholder }}" autocomplete="{{ $autocomplete }}" required data-validate data-required-message="Vui lòng nhập {{ mb_strtolower($label) }}." @if($name==='name') minlength="2" @endif @if($name==='phone') inputmode="numeric" pattern="0[0-9]{9}" maxlength="10" data-pattern-message="Số điện thoại phải gồm 10 chữ số và bắt đầu bằng 0." @elseif($name==='email') data-type-message="Email chưa đúng định dạng." @endif aria-describedby="{{ $name }}-error">
            <p class="field-error" id="{{ $name }}-error" data-error-for="{{ $name }}">@error($name)<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror</p>
        </div>
        @endforeach

        <div class="form-group text-start">
            <label for="password" class="form-label">Mật khẩu</label>
            <div class="password-wrapper"><input id="password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" placeholder="Ít nhất 8 ký tự" autocomplete="new-password" minlength="8" required data-validate data-required-message="Vui lòng tạo mật khẩu." data-min-message="Mật khẩu cần có ít nhất 8 ký tự." aria-describedby="password-error"><button type="button" class="toggle-password" aria-label="Hiện mật khẩu"><i class="bi bi-eye" aria-hidden="true"></i></button></div>
            <p class="field-error" id="password-error" data-error-for="password">@error('password')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror</p>
        </div>
        <div class="form-group text-start">
            <label for="password_confirmation" class="form-label">Xác nhận mật khẩu</label>
            <div class="password-wrapper"><input id="password_confirmation" name="password_confirmation" type="password" class="form-control" placeholder="Nhập lại mật khẩu" autocomplete="new-password" minlength="8" required data-validate data-required-message="Vui lòng xác nhận mật khẩu." aria-describedby="password_confirmation-error"><button type="button" class="toggle-password" aria-label="Hiện mật khẩu"><i class="bi bi-eye" aria-hidden="true"></i></button></div>
            <p class="field-error" id="password_confirmation-error" data-error-for="password_confirmation"></p>
        </div>

        <button type="submit" class="btn btn-auth mt-2">Tạo tài khoản</button>
        <div class="divider">HOẶC</div>
        <p class="text-center mb-0">Đã có tài khoản? <a href="{{ route('login') }}">Đăng nhập</a></p>
    </form>
</div>
@endsection
