<footer class="site-footer supaste-footer {{ request()->routeIs('home') ? 'supaste-footer--flying' : '' }}" aria-label="Chân trang Rosaliza Hotel">
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


    @if (request()->routeIs('home'))
        <div class="flying-button" data-flying-button data-theme="dark" data-trigger="hover" data-href="{{ route('rooms.index') }}">
            <svg class="flying-button__ramp" style="display:none" aria-hidden="true"><path class="flying-button__ramp-body"></path><path class="flying-button__ramp-edge"></path></svg>
            <canvas class="flying-button__smoke" aria-hidden="true"></canvas>
            <button type="button" class="flying-button__craft">
                <span class="flying-button__face" aria-hidden="true"></span>
                <span class="flying-button__label">Đặt phòng ngay</span>
                <span class="flying-button__pod" style="left:20%" aria-hidden="true"></span>
                <span class="flying-button__pod" style="left:80%" aria-hidden="true"></span>
            </button>
            <svg class="flying-button__cursor" viewBox="0 0 24 34" aria-hidden="true"><path d="M2.2 2.1v24.6l6.1-5.9 4.1 9.6 4.4-1.9-4.1-9.4h8.6z" fill="#0a0a0c" stroke="#fff" stroke-width="1.9" stroke-linejoin="round"></path></svg>
        </div>
    @endif

    <div class="supaste-footer__container">
        {{-- Left Column: Brand, Headline, Desc, CTA, Copyright, Team --}}
        <div class="supaste-footer__left">
            <a href="{{ route('home') }}" class="supaste-footer__brand" aria-label="Về trang chủ Rosa Hotel">
                <img src="{{ asset('images/branding/rosa_mascot_flamingo_white_transparent.png') }}" alt="Rosa Hotel" class="supaste-footer__logo-img">
            </a>

            <div class="supaste-footer__headline">
                <span class="supaste-footer__headline-sans">Rosa Hotel</span>
                <span class="supaste-footer__headline-serif">Rest effortlessly.</span>
            </div>

            <p class="supaste-footer__desc">
                Rosaliza Hotel mang đến không gian nghỉ dưỡng tĩnh lặng và tinh tế, nơi mỗi dịch vụ được chăm chút tỉ mỉ cho kỳ nghỉ đáng nhớ của bạn.
            </p>

            @unless (request()->routeIs('home'))
            <a href="{{ route('rooms.index') }}" class="supaste-footer-btn">
                <i class="bi bi-calendar-check" aria-hidden="true"></i>
                <span>Khám phá &amp; Đặt phòng</span>
            </a>
            @endunless

            <div class="supaste-footer__copyright">
                © {{ date('Y') }} Rosaliza Hotel - All rights reserved
            </div>

            <div class="supaste-footer__credit">
                <span>Được phát triển bởi</span>
                <span>Rosaliza Hotel Team</span>
            </div>
        </div>

        {{-- Column 1: Menu --}}
        <div class="supaste-footer__col">
            <h6>Menu</h6>
            <nav class="supaste-footer__nav" aria-label="Menu liên kết nhanh">
                <a href="{{ route('home') }}">Trang chủ</a>
                <a href="{{ route('rooms.index') }}">Phòng nghỉ</a>
                <a href="{{ route('home') }}#hotel-accommodation-rooms">Hạng phòng nổi bật</a>
                <a href="{{ route('rooms.index') }}">Giá phòng</a>
                <a href="{{ route('home') }}#hotel-activities">Trải nghiệm Hà Nội</a>
            </nav>
        </div>

        {{-- Column 2: Navigation --}}
        <div class="supaste-footer__col">
            <h6>Navigation</h6>
            <nav class="supaste-footer__nav" aria-label="Điều hướng chính sách">
                <a href="{{ route('contact') }}">Giới thiệu &amp; Liên hệ</a>
                @if(session('user_id'))
                    <a href="{{ route('booking.mine') }}">Lịch sử đặt phòng</a>
                @else
                    <a href="{{ route('login') }}">Đăng nhập tài khoản</a>
                @endif
                <a href="{{ route('home') }}#hotel-faqs">Chính sách đặt phòng</a>
                <a href="{{ route('home') }}#hotel-faqs">Điều khoản lưu trú</a>
                <a href="{{ route('contact') }}">Cổng hỗ trợ khách hàng</a>
            </nav>
        </div>

        {{-- Column 3: Hạng phòng & Dịch vụ --}}
        <div class="supaste-footer__col">
            <h6>Hạng phòng &amp; Dịch vụ</h6>
            <nav class="supaste-footer__nav" aria-label="Hạng phòng và dịch vụ cao cấp">
                <a href="{{ route('rooms.index') }}">Xem các hạng phòng</a>
                <a href="{{ route('rooms.index') }}">Tìm phòng trống</a>
                <a href="{{ route('rooms.index') }}">Đặt phòng trực tuyến</a>
                <a href="{{ route('contact') }}">Liên hệ hỗ trợ</a>
                <a href="{{ route('contact') }}">Thông tin khách sạn</a>
            </nav>
        </div>
    </div>
</footer>
