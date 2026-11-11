{{-- =========================================================================
     Royal Hotel — Apple iOS Mobile Tab Bar & Navigation Drawer System
     Strictly active on screens <= 768px (Desktop & Tablet > 768px untouched)
     Design inspired by Apple iOS UITabBar, SwiftUI TabView & NameThatUI
     ========================================================================= --}}

{{-- 1. Apple Mobile Floating Hamburger Button --}}
<button type="button"
        class="mobile-hamburger-btn"
        aria-label="Mở menu điều hướng"
        aria-expanded="false"
        aria-controls="mobile-nav-drawer"
        data-drawer-open>
    <span class="hamburger-bar" aria-hidden="true"></span>
    <span class="hamburger-bar" aria-hidden="true"></span>
    <span class="hamburger-bar" aria-hidden="true"></span>
</button>

{{-- 2. Navigation Drawer Scrim / Overlay --}}
<div class="mobile-drawer-scrim" data-drawer-scrim aria-hidden="true"></div>

{{-- 3. Navigation Drawer (Off-canvas Sheet side="left") --}}
<nav id="mobile-nav-drawer"
     class="mobile-nav-drawer"
     aria-label="Menu điều hướng mở rộng"
     aria-modal="true"
     role="dialog"
     data-lenis-prevent
     inert>
    <div class="mobile-drawer-header">
        <a href="{{ route('home') }}" class="mobile-drawer-brand" style="display: inline-flex; align-items: center; gap: 10px; text-decoration: none;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 10px; background: #0f172a; flex-shrink: 0;">
                <img src="{{ asset('images/branding/rosa_mascot_flamingo_white_transparent.png') }}" alt="Rosa Hotel" style="height: 22px; width: auto; object-fit: contain;">
            </span>
            <span class="mobile-drawer-title" style="margin: 0; color: #0f172a;">Rosa Hotel</span>
        </a>
        <button type="button" class="mobile-drawer-close" aria-label="Đóng menu" data-drawer-close>
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="mobile-drawer-body" data-lenis-prevent>
        <div class="mobile-drawer-section-label">ĐIỀU HƯỚNG CHÍNH</div>
        <a href="{{ route('home') }}" class="mobile-drawer-link {{ request()->routeIs('home') ? 'is-active' : '' }}">
            <i class="bi bi-house-door" aria-hidden="true"></i>
            <span>Trang chủ</span>
        </a>
        <a href="{{ route('rooms.index') }}" class="mobile-drawer-link {{ request()->routeIs('rooms.*') ? 'is-active' : '' }}">
            <i class="bi bi-door-open" aria-hidden="true"></i>
            <span>Phòng</span>
        </a>
        <a href="{{ route('contact') }}" class="mobile-drawer-link {{ request()->routeIs('contact') ? 'is-active' : '' }}">
            <i class="bi bi-geo-alt" aria-hidden="true"></i>
            <span>Giới thiệu &amp; Liên hệ</span>
        </a>

        <div class="mobile-drawer-section-label">TÀI KHOẢN &amp; LƯU TRÚ</div>
        @if(session('user_id'))
            <a href="{{ route('booking.mine') }}" class="mobile-drawer-link {{ request()->routeIs('booking.mine') ? 'is-active' : '' }}">
                <i class="bi bi-suitcase2" aria-hidden="true"></i>
                <span>Lịch sử đặt phòng</span>
            </a>
            <a href="{{ route('account.show') }}" class="mobile-drawer-link {{ request()->routeIs('account.*') ? 'is-active' : '' }}">
                <i class="bi bi-person" aria-hidden="true"></i>
                <span>Thông tin cá nhân</span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mobile-drawer-logout-form">
                @csrf
                <button type="submit" class="mobile-drawer-link mobile-drawer-link--danger">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    <span>Đăng xuất</span>
                </button>
            </form>
        @else
            <a href="{{ route('login') }}" class="mobile-drawer-link {{ request()->routeIs('login') ? 'is-active' : '' }}">
                <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                <span>Đăng nhập / Đăng ký</span>
            </a>
        @endif


    </div>

    <div class="mobile-drawer-footer">
        <div class="mobile-drawer-contact-line">
            <i class="bi bi-telephone" aria-hidden="true"></i>
            <span>Hotline: 024 3828 9999</span>
        </div>
        <div class="mobile-drawer-contact-line">
            <i class="bi bi-envelope" aria-hidden="true"></i>
            <span>contact@rosalizahotel.vn</span>
        </div>
    </div>
</nav>

{{-- 4. Mobile Bottom Tab Bar removed per user request: switched to mobile hamburger navigation and floating chatbot --}}

{{-- 5. Native iOS Navigation Drawer Script --}}
<script>
(function() {
    function initMobileDrawer() {
        var openBtn = document.querySelector('[data-drawer-open]');
        var closeBtn = document.querySelector('[data-drawer-close]');
        var scrim = document.querySelector('[data-drawer-scrim]');
        var drawer = document.getElementById('mobile-nav-drawer');
        if (!openBtn || !drawer) return;

        function openDrawer() {
            if (window.closeRoyalChat) {
                window.closeRoyalChat();
            } else if (window.toggleRoyalChat) {
                window.toggleRoyalChat(false);
            }
            drawer.removeAttribute('inert');
            drawer.classList.add('is-open');
            if (scrim) scrim.classList.add('is-open');
            openBtn.setAttribute('aria-expanded', 'true');
            openBtn.classList.add('is-active');
            document.body.classList.add('mobile-drawer-locked');
            if (closeBtn) closeBtn.focus();
        }

        function closeDrawer(restoreFocus) {
            if (!drawer.classList.contains('is-open')) return;
            drawer.setAttribute('inert', '');
            drawer.classList.remove('is-open');
            if (scrim) scrim.classList.remove('is-open');
            openBtn.setAttribute('aria-expanded', 'false');
            openBtn.classList.remove('is-active');
            document.body.classList.remove('mobile-drawer-locked');
            if (restoreFocus !== false) openBtn.focus();
        }

        window.closeMobileDrawer = function() {
            closeDrawer(false);
        };

        drawer.querySelectorAll('a.mobile-drawer-link, a.mobile-drawer-brand').forEach(function(link) {
            link.addEventListener('click', function() {
                closeDrawer(false);
            });
        });

        window.addEventListener('pageshow', function() {
            closeDrawer(false);
        });

        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (drawer.classList.contains('is-open')) {
                closeDrawer();
            } else {
                openDrawer();
            }
        });

        if (closeBtn) {
            closeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeDrawer();
            });
        }

        if (scrim) {
            scrim.addEventListener('click', function() {
                closeDrawer();
            });
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
                closeDrawer();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileDrawer);
    } else {
        initMobileDrawer();
    }
})();
</script>
