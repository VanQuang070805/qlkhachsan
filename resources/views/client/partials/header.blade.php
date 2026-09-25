<header class="site-header" data-site-header @if(request()->routeIs('home', 'rooms.index', 'rooms.search', 'rooms.detail', 'contact')) data-home-header @endif @if(request()->routeIs('home')) data-homepage-header @endif>
    <div class="site-header__inner">
        <span class="site-header__brand-spacer" aria-hidden="true"></span>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation" data-nav-toggle><span class="sr-only">Mở menu</span><span></span><span></span></button>
        <nav class="primary-nav" id="primary-navigation" aria-label="Điều hướng chính" data-primary-nav>
            <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}">Trang chủ</a><a class="{{ request()->routeIs('rooms.*') ? 'is-active' : '' }}" href="{{ route('rooms.index') }}">Phòng nghỉ</a><a class="{{ request()->routeIs('contact') ? 'is-active' : '' }}" href="{{ route('contact') }}">Về Royal & Liên hệ</a>
            <span class="primary-nav__divider" aria-hidden="true"></span>
            @if(session('user_id'))<a class="primary-nav__mobile-account" href="{{ route('booking.mine') }}">Kỳ nghỉ của tôi</a><a class="primary-nav__mobile-account" href="{{ route('account.show') }}">Thông tin cá nhân</a>@else<a class="primary-nav__mobile-account" href="{{ route('login') }}">Đăng nhập</a>@endif
        </nav>
        <div class="site-header__actions">@if(session('user_id'))<details class="account-menu"><summary class="account-link account-icon" aria-label="Tài khoản của tôi" title="Tài khoản của tôi"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/></svg></summary><div class="account-menu__panel"><a href="{{ route('booking.mine') }}"><i class="bi bi-suitcase2"></i>Kỳ nghỉ của tôi</a><a href="{{ route('account.show') }}"><i class="bi bi-person"></i>Thông tin cá nhân</a></div></details><form class="header-logout" method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="account-link">Đăng xuất</button></form>@else<a class="account-link" href="{{ route('login') }}">Đăng nhập</a>@endif<a class="button button--small" href="{{ route('rooms.index') }}">Đặt phòng</a></div>
    </div>
</header>
