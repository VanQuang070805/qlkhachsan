@extends('layouts.main')

@section('title', 'Thông tin cá nhân · Royal Hotel')

@section('content')
<section class="commerce-page account-profile" aria-labelledby="account-title">
    <header class="commerce-heading" data-reveal>
        <p class="editorial-eyebrow">Your account</p>
        <h1 id="account-title">Your profile</h1>
        <p>Giữ thông tin liên hệ chính xác để mỗi lần đặt phòng diễn ra nhẹ nhàng.</p>
        <nav class="account-switcher" aria-label="Khu vực tài khoản"><a href="{{ route('booking.mine') }}">Kỳ nghỉ</a><a class="is-active" href="{{ route('account.show') }}">Thông tin cá nhân</a></nav>
    </header>

    <div class="account-profile__layout">
        <aside class="account-profile__identity" data-reveal>
            <span class="account-avatar">{{ mb_strtoupper(mb_substr($user->fullname ?: 'R', 0, 1)) }}</span>
            <p class="editorial-eyebrow">Royal member</p>
            <h2>{{ $user->fullname }}</h2>
            <p>{{ $user->email }}</p>
            <div><span><i class="bi bi-shield-check" aria-hidden="true"></i> Tài khoản đã xác thực</span><span><i class="bi bi-calendar2-check" aria-hidden="true"></i> {{ $user->bookings()->count() }} kỳ nghỉ</span></div>
        </aside>
        <form class="account-profile__form" action="{{ route('account.update') }}" method="POST" data-reveal>
            @csrf @method('PATCH')
            <div class="account-profile__form-head"><div><p class="editorial-eyebrow">Contact profile</p><h2>Your details</h2></div><i class="bi bi-person-lines-fill" aria-hidden="true"></i></div>
            @foreach([['fullname','Họ và tên','text','Nguyễn Văn A'],['email','Email','email','you@example.com'],['phone','Số điện thoại','tel','0912345678']] as [$name,$label,$type,$placeholder])
            <label class="account-field"><span>{{ $label }}</span><input type="{{ $type }}" name="{{ $name }}" value="{{ old($name,$user->$name) }}" placeholder="{{ $placeholder }}" @if($name==='phone') inputmode="numeric" maxlength="10" @endif required>@error($name)<small><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</small>@enderror</label>
            @endforeach
            <button class="button" type="submit">Lưu thay đổi</button>
            <a class="account-password-link" href="{{ route('password.forgot') }}"><i class="bi bi-envelope-lock"></i><span><strong>Đổi hoặc quên mật khẩu</strong><small>Nhận mã xác minh qua email {{ $user->email }}</small></span><i class="bi bi-arrow-right"></i></a>
        </form>
    </div>
</section>
@endsection
