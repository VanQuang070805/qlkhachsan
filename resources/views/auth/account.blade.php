@extends('layouts.main')

@section('title', 'Thông tin cá nhân · Rosaliza Hotel')

@section('content')
<section class="commerce-page account-profile" aria-labelledby="account-title">
    <header class="commerce-heading" data-reveal>
        <p class="editorial-eyebrow">Tài khoản của bạn</p>
        <h1 id="account-title">Hồ sơ cá nhân</h1>
        <p>Giữ thông tin liên hệ chính xác để mỗi lần đặt phòng diễn ra nhẹ nhàng.</p>
        <nav class="account-switcher" aria-label="Khu vực tài khoản"><a href="{{ route('booking.mine') }}">Kỳ nghỉ</a><a class="is-active" href="{{ route('account.show') }}">Thông tin cá nhân</a></nav>
    </header>

    <div class="account-profile__layout">
        <aside class="account-profile__identity" data-reveal style="padding: 0 !important; overflow: hidden; border-radius: 24px;">
            <div class="macos-card-bar" style="border-radius: 0; padding: 14px 20px; border-bottom: 1px solid rgba(0,0,0,0.06); background: #f8fafc; margin: 0; display: flex !important; flex-direction: row !important; align-items: center !important; justify-content: space-between !important;">
                <span class="macos-card-title">Hồ sơ thành viên</span>
                <i class="bi bi-shield-check" style="color:#10b981;" aria-hidden="true"></i>
            </div>
            <div style="padding: 32px 24px; text-align: center; display: flex; flex-direction: column; align-items: center;">
                @if($user->avatar_url)
                    <img src="{{ $user->avatar_url }}" alt="Ảnh đại diện {{ $user->fullname }}" referrerpolicy="no-referrer" style="margin: 0 auto 16px; width: 68px; height: 68px; object-fit: cover; border-radius: 50%; border: 2px solid #B89C72; box-shadow: 0 8px 20px rgba(24, 23, 21, 0.16);">
                @else
                    <span class="account-avatar" style="margin: 0 auto 16px; width: 68px; height: 68px; font-size: 26px; border-radius: 50%; display: grid; place-items: center; background: linear-gradient(135deg, #181715, #3f3f46); color: #B89C72; box-shadow: 0 8px 20px rgba(24, 23, 21, 0.2);">{{ mb_strtoupper(mb_substr($user->fullname ?: 'R', 0, 1)) }}</span>
                @endif
                <p class="editorial-eyebrow" style="margin-bottom: 4px;">Thành viên Rosaliza</p>
                <h2 style="margin: 0 0 6px; font-size: 20px; font-weight: 600; color: #0f172a;">{{ $user->fullname }}</h2>
                <p style="margin: 0 0 20px; font-size: 13px; color: #475569; word-break: break-word;">{{ $user->email }}</p>
                <div class="account-profile__stats" style="width: 100%; display: flex; flex-direction: column; gap: 8px; border-top: 1px solid rgba(0,0,0,0.06); padding-top: 16px;">
                    <span style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12.5px; color: #334155;"><i class="bi bi-shield-check text-emerald-600" aria-hidden="true"></i> Tài khoản đã xác thực</span>
                    <span style="display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12.5px; color: #334155;"><i class="bi bi-calendar2-check text-primary" aria-hidden="true"></i> {{ $user->bookings()->count() }} kỳ nghỉ</span>
                </div>
            </div>
        </aside>
        <form class="account-profile__form" action="{{ route('account.update') }}" method="POST" data-reveal style="padding: 0 !important; overflow: hidden; border-radius: 24px;">
            @csrf @method('PATCH')
            <div class="macos-card-bar" style="border-radius: 0; padding: 14px 20px; border-bottom: 1px solid rgba(0,0,0,0.06); background: #f8fafc; margin: 0; display: flex; align-items: center; justify-content: space-between;">
                <span class="macos-card-title">Cài đặt thông tin</span>
                <i class="bi bi-sliders2" style="color: #64748b;" aria-hidden="true"></i>
            </div>
            <div class="account-profile__form-inner" style="padding: 28px 24px;">
                @if(session('success'))
                    <p role="status" style="margin: 0 0 18px; padding: 11px 16px; border-radius: 14px; background: rgba(22, 163, 74, 0.10); color: #166534; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-check-circle-fill" aria-hidden="true"></i><span>{{ session('success') }}</span>
                    </p>
                @endif
                @if(session('error'))
                    <p role="alert" style="margin: 0 0 18px; padding: 11px 16px; border-radius: 14px; background: rgba(220, 38, 38, 0.08); color: #991b1b; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                        <i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i><span>{{ session('error') }}</span>
                    </p>
                @endif
                <div class="account-profile__form-head"><div><p class="editorial-eyebrow">Thông tin liên hệ</p><h2>Chi tiết hồ sơ</h2></div><i class="bi bi-person-lines-fill" aria-hidden="true"></i></div>
                @foreach([['fullname','Họ và tên','text','Nguyễn Văn A'],['email','Email','email','you@example.com'],['phone','Số điện thoại','tel','0912345678']] as [$name,$label,$type,$placeholder])
                <label class="account-field"><span>{{ $label }}</span><input type="{{ $type }}" name="{{ $name }}" value="{{ old($name,$user->$name) }}" placeholder="{{ $placeholder }}" @if($name==='phone') inputmode="numeric" pattern="0[0-9]{9}" maxlength="10" data-digits-only @endif required>@error($name)<small><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</small>@enderror</label>
                @endforeach
                <button class="button" type="submit">Lưu thay đổi</button>
                @if($user->google_id)
                    <div class="account-password-link" style="margin-top: 14px; cursor: default;" role="status">
                        <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true" style="flex-shrink: 0;">
                            <path fill="#4285F4" d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.797 2.716v2.259h2.909c1.702-1.567 2.684-3.875 2.684-6.615Z"/>
                            <path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.956-2.18l-2.909-2.259c-.806.54-1.835.859-3.047.859-2.344 0-4.328-1.585-5.037-3.714H.956v2.332A9 9 0 0 0 9 18Z"/>
                            <path fill="#FBBC05" d="M3.963 10.706A5.41 5.41 0 0 1 3.682 9c0-.592.102-1.168.281-1.706V4.962H.956A9 9 0 0 0 0 9c0 1.452.347 2.827.956 4.038l3.007-2.332Z"/>
                            <path fill="#EA4335" d="M9 3.58c1.321 0 2.507.454 3.441 1.346l2.581-2.581C13.464.892 11.426 0 9 0A9 9 0 0 0 .956 4.962l3.007 2.332C4.672 5.165 6.656 3.58 9 3.58Z"/>
                        </svg>
                        <span><strong>Đã liên kết tài khoản Google</strong><small>Đăng nhập nhanh qua {{ $user->email }}</small></span>
                        <i class="bi bi-check2-circle" style="color: #166534;" aria-hidden="true"></i>
                    </div>
                @else
                    <a class="account-password-link" href="{{ route('auth.google', ['intent' => 'link']) }}" data-no-transition style="margin-top: 14px;">
                        <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true" style="flex-shrink: 0;">
                            <path fill="#4285F4" d="M17.64 9.205c0-.639-.057-1.252-.164-1.841H9v3.481h4.844a4.14 4.14 0 0 1-1.797 2.716v2.259h2.909c1.702-1.567 2.684-3.875 2.684-6.615Z"/>
                            <path fill="#34A853" d="M9 18c2.43 0 4.468-.806 5.956-2.18l-2.909-2.259c-.806.54-1.835.859-3.047.859-2.344 0-4.328-1.585-5.037-3.714H.956v2.332A9 9 0 0 0 9 18Z"/>
                            <path fill="#FBBC05" d="M3.963 10.706A5.41 5.41 0 0 1 3.682 9c0-.592.102-1.168.281-1.706V4.962H.956A9 9 0 0 0 0 9c0 1.452.347 2.827.956 4.038l3.007-2.332Z"/>
                            <path fill="#EA4335" d="M9 3.58c1.321 0 2.507.454 3.441 1.346l2.581-2.581C13.464.892 11.426 0 9 0A9 9 0 0 0 .956 4.962l3.007 2.332C4.672 5.165 6.656 3.58 9 3.58Z"/>
                        </svg>
                        <span><strong>Liên kết tài khoản Google</strong><small>Cho phép đăng nhập một chạm bằng {{ $user->email }}</small></span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                @endif
                <a class="account-password-link" href="{{ route('password.forgot') }}"><i class="bi bi-envelope-lock" aria-hidden="true"></i><span><strong>Đổi hoặc quên mật khẩu</strong><small>Nhận mã xác minh qua email {{ $user->email }}</small></span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </div>
        </form>
    </div>
</section>
@endsection
