@extends('layouts.main')

@section('title', 'Thông tin cá nhân · Posh Boutique')

@section('content')
<section class="commerce-page account-profile" aria-labelledby="account-title">
    <header class="commerce-heading" data-reveal>
        <p class="editorial-eyebrow">Your account</p>
        <h1 id="account-title">Your profile</h1>
        <p>Giữ thông tin liên hệ chính xác để mỗi lần đặt phòng diễn ra nhẹ nhàng.</p>
        <nav class="account-switcher" aria-label="Khu vực tài khoản"><a href="{{ route('booking.mine') }}">Kỳ nghỉ</a><a class="is-active" href="{{ route('account.show') }}">Thông tin cá nhân</a></nav>
    </header>

    <div class="account-profile__layout">
        <aside class="account-profile__identity" data-reveal style="padding: 0 !important; overflow: hidden; border-radius: 24px;">
            <div class="macos-card-bar" style="border-radius: 0; padding: 12px 18px; border-bottom: 1px solid rgba(0,0,0,0.06); background: #f8fafc; margin: 0; display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important;">
                <div style="display:flex !important; flex-direction:row !important; align-items:center !important; gap:6px !important; border:none !important; padding:0 !important; margin:0 !important;">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <span class="macos-card-title">Member Profile</span>
                <i class="bi bi-shield-check" style="color:#10b981;"></i>
            </div>
            <div style="padding: 32px 24px; text-align: center; display: flex; flex-direction: column; align-items: center;">
                <span class="account-avatar" style="margin: 0 auto 16px; width: 68px; height: 68px; font-size: 26px; border-radius: 50%; display: grid; place-items: center; background: linear-gradient(135deg, #0071e3, #0056b3); color: #fff; box-shadow: 0 8px 20px rgba(0, 113, 227, 0.25);">{{ mb_strtoupper(mb_substr($user->fullname ?: 'R', 0, 1)) }}</span>
                <p class="editorial-eyebrow" style="margin-bottom: 4px;">Royal member</p>
                <h2 style="margin: 0 0 6px; font-size: 20px; font-weight: 700; color: #0f172a;">{{ $user->fullname }}</h2>
                <p style="margin: 0 0 20px; font-size: 13px; color: #64748b;">{{ $user->email }}</p>
                <div class="account-profile__stats" style="width: 100%; display: flex; flex-direction: column; gap: 8px; border-top: 1px solid rgba(0,0,0,0.06); padding-top: 16px;">
                    <span style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12.5px; color: #334155;"><i class="bi bi-shield-check text-emerald-600" aria-hidden="true"></i> Tài khoản đã xác thực</span>
                    <span style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12.5px; color: #334155;"><i class="bi bi-calendar2-check text-primary" aria-hidden="true"></i> {{ $user->bookings()->count() }} kỳ nghỉ</span>
                </div>
            </div>
        </aside>
        <form class="account-profile__form" action="{{ route('account.update') }}" method="POST" data-reveal style="padding: 0 !important; overflow: hidden; border-radius: 24px;">
            @csrf @method('PATCH')
            <div class="macos-card-bar" style="border-radius: 0; padding: 12px 18px; border-bottom: 1px solid rgba(0,0,0,0.06); background: #f8fafc; margin: 0;">
                <div style="display:flex;gap:6px;">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <span class="macos-card-title">Cài đặt thông tin</span>
                <div style="width: 20px;"></div>
            </div>
            <div style="padding: 28px 28px;">
                <div class="account-profile__form-head"><div><p class="editorial-eyebrow">Contact profile</p><h2>Your details</h2></div><i class="bi bi-person-lines-fill" aria-hidden="true"></i></div>
                @foreach([['fullname','Họ và tên','text','Nguyễn Văn A'],['email','Email','email','you@example.com'],['phone','Số điện thoại','tel','0912345678']] as [$name,$label,$type,$placeholder])
                <label class="account-field"><span>{{ $label }}</span><input type="{{ $type }}" name="{{ $name }}" value="{{ old($name,$user->$name) }}" placeholder="{{ $placeholder }}" @if($name==='phone') inputmode="numeric" maxlength="10" @endif required>@error($name)<small><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</small>@enderror</label>
                @endforeach
                <button class="button" type="submit">Lưu thay đổi</button>
                <a class="account-password-link" href="{{ route('password.forgot') }}"><i class="bi bi-envelope-lock"></i><span><strong>Đổi hoặc quên mật khẩu</strong><small>Nhận mã xác minh qua email {{ $user->email }}</small></span><i class="bi bi-arrow-right"></i></a>
            </div>
        </form>
    </div>
</section>
@endsection
