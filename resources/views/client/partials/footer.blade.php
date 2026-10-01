<footer class="site-footer supaste-footer" aria-label="Chân trang Posh Boutique">
    {{-- Top Inverted Notch / Tongue dipping from white content above --}}
    <div class="supaste-footer__tongue-wrap" aria-hidden="true">
        <div class="supaste-footer__tongue">
            <svg class="supaste-footer__fillet-left" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M 0 0 L 20 0 C 8.954 0 0 8.954 0 20 Z" fill="#ffffff" transform="scale(-1, 1) translate(-20, 0)"/>
            </svg>
            <svg class="supaste-footer__fillet-right" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M 0 0 L 20 0 C 8.954 0 0 8.954 0 20 Z" fill="#ffffff"/>
            </svg>
        </div>
    </div>

    {{-- Honors Badge on Right Edge (1:1 with Supaste W. Honors badge in Image 1) --}}
    <div class="supaste-footer__honors" title="Posh Boutique — 5-Star Quiet Luxury Stay">
        <span class="w-icon">W.</span>
        <span class="v-text">Honors</span>
    </div>

    <div class="supaste-footer__container">
        {{-- Left Column: Brand, Headline, Desc, CTA, Copyright, Team --}}
        <div class="supaste-footer__left">
            <a href="{{ route('home') }}" class="supaste-footer__brand" aria-label="Về trang chủ Posh Boutique">
                <img src="{{ asset('aura-logo-white.png') }}" alt="Posh Boutique Logo" class="supaste-footer__logo-img">
                <span class="supaste-footer__brand-title">Posh Boutique</span>
            </a>

            <div class="supaste-footer__headline">
                <span class="supaste-footer__headline-sans">Posh Boutique</span>
                <span class="supaste-footer__headline-serif">Rest effortlessly.</span>
            </div>

            <p class="supaste-footer__desc">
                Posh Boutique mang đến không gian nghỉ dưỡng tĩnh lặng và tinh tế, nơi mỗi dịch vụ được chăm chút tỉ mỉ cho kỳ nghỉ đáng nhớ của bạn.
            </p>

            <a href="{{ route('rooms.index') }}" class="supaste-footer-btn">
                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                <span>Khám phá &amp; Đặt phòng</span>
            </a>

            <div class="supaste-footer__copyright">
                © {{ date('Y') }} Posh Boutique - All rights reserved
            </div>

            <div class="supaste-footer__credit">
                <span>Built with 💙 by</span>
                <span>Posh Boutique Team</span>
            </div>
        </div>

        {{-- Column 1: Menu --}}
        <div class="supaste-footer__col">
            <h6>Menu</h6>
            <nav class="supaste-footer__nav" aria-label="Menu liên kết nhanh">
                <a href="{{ route('home') }}">Trang chủ</a>
                <a href="{{ route('rooms.index') }}">Phòng nghỉ</a>
                <a href="{{ route('home') }}#features">Đặc quyền</a>
                <a href="{{ route('home') }}#pricing">Bảng giá</a>
                <a href="{{ route('home') }}#reviews">Đánh giá</a>
            </nav>
        </div>

        {{-- Column 2: Navigation --}}
        <div class="supaste-footer__col">
            <h6>Navigation</h6>
            <nav class="supaste-footer__nav" aria-label="Điều hướng chính sách">
                <a href="{{ route('contact') }}">Về Posh &amp; Liên hệ</a>
                @if(session('user_id'))
                    <a href="{{ route('booking.mine') }}">Lịch sử đặt phòng</a>
                @else
                    <a href="{{ route('login') }}">Đăng nhập tài khoản</a>
                @endif
                <a href="{{ route('contact') }}#faq">Chính sách đặt phòng</a>
                <a href="{{ route('contact') }}">Điều khoản lưu trú</a>
                <a href="{{ route('contact') }}">Cổng hỗ trợ khách hàng</a>
            </nav>
        </div>

        {{-- Column 3: Hạng phòng & Dịch vụ --}}
        <div class="supaste-footer__col">
            <h6>Hạng phòng &amp; Dịch vụ</h6>
            <nav class="supaste-footer__nav" aria-label="Hạng phòng và dịch vụ cao cấp">
                <a href="{{ route('rooms.index') }}">Deluxe Queen Suite</a>
                <a href="{{ route('rooms.index') }}">Posh Executive Suite</a>
                <a href="{{ route('rooms.index') }}">Presidential Suite</a>
                <a href="{{ route('contact') }}">In-room Dining 24/7</a>
                <a href="{{ route('contact') }}">Spa &amp; Hồ bơi vô cực</a>
            </nav>
        </div>
    </div>
</footer>
