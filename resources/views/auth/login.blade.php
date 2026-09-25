@extends('layouts.auth')

@section('content')
<div class="auth-card">
    <header class="auth-intro">
        @include('auth.partials.brand')
        <p class="subtitle">Trở lại hành trình của bạn</p>
    </header>
    @include('auth.partials.form-status')

    <div id="customer-card" class="{{ old('username') ? 'd-none' : '' }}">
        <form action="{{ route('login') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
            @csrf <input type="hidden" name="role" value="customer">
            <a class="auth-provider" href="{{ route('auth.google') }}" data-no-transition><svg class="auth-provider__mark" viewBox="0 0 18 18" aria-hidden="true"><path fill="#4285F4" d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.797 2.716v2.259h2.909c1.702-1.567 2.684-3.875 2.684-6.615Z"/><path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.956-2.18l-2.909-2.259c-.806.54-1.835.859-3.047.859-2.344 0-4.328-1.585-5.037-3.714H.956v2.332A9 9 0 0 0 9 18Z"/><path fill="#FBBC05" d="M3.963 10.706A5.41 5.41 0 0 1 3.682 9c0-.592.102-1.168.281-1.706V4.962H.956A9 9 0 0 0 0 9c0 1.452.347 2.827.956 4.038l3.007-2.332Z"/><path fill="#EA4335" d="M9 3.58c1.321 0 2.507.454 3.441 1.346l2.581-2.581C13.464.892 11.426 0 9 0A9 9 0 0 0 .956 4.962l3.007 2.332C4.672 5.165 6.656 3.58 9 3.58Z"/></svg><span>Continue with Google</span></a>
            <div class="divider"><span>hoặc</span></div>
            <div class="form-group text-start"><label for="email" class="form-label">Email</label><input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" placeholder="you@example.com" required value="{{ old('email', request()->cookie('royal_remembered_email')) }}" autocomplete="username" inputmode="email" data-validate data-required-message="Vui lòng nhập email." data-type-message="Email chưa đúng định dạng." aria-describedby="email-error"><p class="field-error" id="email-error" data-error-for="email">@error('email')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror</p></div>
            <div class="form-group text-start"><label for="password" class="form-label">Mật khẩu</label><div class="password-wrapper"><input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="Nhập mật khẩu" required autocomplete="current-password" data-validate data-required-message="Vui lòng nhập mật khẩu." aria-describedby="password-error"><button type="button" class="toggle-password" aria-label="Hiện mật khẩu"><i class="bi bi-eye" aria-hidden="true"></i></button></div><p class="field-error" id="password-error" data-error-for="password">@error('password')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror</p><div class="auth-options"><label><input type="checkbox" name="remember" value="1" @checked(old('remember', true))> Duy trì đăng nhập</label><a href="{{ route('password.forgot') }}" class="small">Quên mật khẩu?</a></div></div>
            <button type="submit" class="btn btn-auth">Đăng nhập</button>
            <p class="auth-register-link text-center mb-2">Chưa có tài khoản? <a href="{{ route('register') }}">Đăng ký ngay</a></p>
            <p class="text-center mb-0 small">Bạn là nhân viên? <a href="#" id="show-staff-link">Đăng nhập hệ thống</a></p>
        </form>
    </div>

    <div id="staff-card" class="{{ old('username') ? '' : 'd-none' }}">
        <form action="{{ route('login') }}" method="POST" autocomplete="on" novalidate data-inline-validation>
            @csrf <input type="hidden" name="role" value="staff">
            <div class="form-group text-start"><label for="staff-username" class="form-label">Tên đăng nhập</label><input type="text" class="form-control @error('username') is-invalid @enderror" id="staff-username" name="username" placeholder="Tên đăng nhập nhân viên" required value="{{ old('username') }}" autocomplete="username" data-validate data-required-message="Vui lòng nhập tên đăng nhập." aria-describedby="username-error"><p class="field-error" id="username-error" data-error-for="username">@error('username')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror</p></div>
            <div class="form-group text-start"><label for="staff-password" class="form-label">Mật khẩu</label><div class="password-wrapper"><input type="password" class="form-control @error('password') is-invalid @enderror" id="staff-password" name="password" placeholder="Nhập mật khẩu" required autocomplete="current-password" data-validate data-required-message="Vui lòng nhập mật khẩu." aria-describedby="staff-password-error"><button type="button" class="toggle-password" aria-label="Hiện mật khẩu"><i class="bi bi-eye" aria-hidden="true"></i></button></div><p class="field-error" id="staff-password-error" data-error-for="password">@error('password')<i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}@enderror</p></div>
            <button type="submit" class="btn btn-auth">Đăng nhập hệ thống</button>
            <div class="divider"><span>hoặc</span></div>
            <p class="text-center mb-0 small">Bạn là khách lưu trú? <a href="#" id="show-customer-link">Đăng nhập khách hàng</a></p>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const customer = document.getElementById('customer-card');
    const staff = document.getElementById('staff-card');
    const switchCards = (hideEl, showEl) => {
        if (window.gsap) {
            gsap.to(hideEl, {
                opacity: 0,
                y: -8,
                duration: 0.18,
                ease: 'power2.in',
                onComplete: () => {
                    hideEl.classList.add('d-none');
                    showEl.classList.remove('d-none');
                    gsap.fromTo(showEl,
                        { opacity: 0, y: 10 },
                        { opacity: 1, y: 0, duration: 0.28, ease: 'power2.out', clearProps: 'transform' }
                    );
                }
            });
        } else {
            hideEl.classList.add('d-none');
            showEl.classList.remove('d-none');
        }
    };
    document.getElementById('show-staff-link')?.addEventListener('click', (event) => {
        event.preventDefault();
        switchCards(customer, staff);
    });
    document.getElementById('show-customer-link')?.addEventListener('click', (event) => {
        event.preventDefault();
        switchCards(staff, customer);
    });
});
</script>
@endpush
