@extends('client.layouts.app')
@section('title', 'Posh Boutique')
@section('content')

{{-- =========================================================================
     HERO SECTION (SUPASTE VIBRANT ELECTRIC BLUE + MAC WINDOW SHOWCASE)
     ========================================================================= --}}
<section class="hero" aria-labelledby="hero-title">
    {{-- 3D Landscape Layer: Background Hills (Behind the Glass Window) --}}
    <div class="supaste-hero__bg" aria-hidden="true">
        <img src="{{ asset('images/home/supaste-hills-bg.jpg') }}" alt="" class="supaste-hero__bg-img">
    </div>

    <div class="hero__content">
        {{-- Supaste Signature Dual-Font Headline --}}
        <h1 id="hero-title" class="supaste-hero__headline">
            <span class="supaste-hero__headline-sans">Posh Boutique</span>
            <span class="supaste-hero__headline-serif">Rest effortlessly.</span>
        </h1>

        {{-- Subtitle --}}
        <p class="supaste-hero__subtitle">
            A quiet luxury sanctuary of bespoke suites and thoughtful hospitality, crafted for deep rest and effortless calm at Posh Boutique.
        </p>

        {{-- Single CTA Button (Filter removed per user request) --}}
        <div class="supaste-hero__action">
            <a href="{{ route('rooms.index') }}" class="supaste-cta-btn">
                <i class="bi bi-search" aria-hidden="true"></i>
                <span>Tìm kiếm phòng trống</span>
                <span style="opacity: 0.6; margin-left: 2px;" aria-hidden="true">→</span>
            </a>
            <div class="supaste-hero__trust" aria-label="Cam kết dịch vụ">
                <span>✓ Xác nhận tức thì</span>
                <span aria-hidden="true">•</span>
                <span>✓ Miễn phí hủy trước 48h</span>
                <span aria-hidden="true">•</span>
                <span>✓ Minh bạch giá</span>
            </div>
        </div>

        {{-- Mac Showcase Window with Dynamic Island Notch --}}
        {{-- Mac Showcase Window with Dynamic Island Notch (1:1 Supaste Clone) --}}
        <div class="supaste-window" aria-label="Giao diện xem trước phòng nghỉ">
            {{-- Window Topbar: Left Wing on Glass, Center Dynamic Island Notch, Right Wing on Glass --}}
            <div class="supaste-window__header">
                {{-- Left Wing: macOS Traffic Lights (Close / Minimize / Zoom) --}}
                <div class="supaste-window__wing supaste-window__wing--left">
                    <div class="window-controls" aria-hidden="true">
                        <span class="ctrl-dot ctrl-red"></span>
                        <span class="ctrl-dot ctrl-yellow"></span>
                        <span class="ctrl-dot ctrl-green"></span>
                    </div>
                </div>

                {{-- Center Dynamic Island Notch (hanging down from top edge) --}}
                <div class="supaste-notch">
                    {{-- Concave corner fillet left --}}
                    <div class="supaste-notch__fillet supaste-notch__fillet--left" aria-hidden="true"></div>

                    {{-- Notch Core --}}
                    <div class="supaste-notch__core">
                        {{-- Row 1: Search & macOS 3 Icon Buttons --}}
                        <div class="supaste-notch__top">
                            <div class="supaste-notch__search">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <span class="supaste-notch__search-text">Tìm kiếm hạng phòng...</span>
                            </div>
                            <div class="supaste-notch__actions" aria-label="Tiện ích macOS">
                                <button type="button" class="macos-icon-btn" title="Hạng phòng yêu thích" aria-label="Yêu thích">
                                    <i class="bi bi-star"></i>
                                </button>
                                <button type="button" class="macos-icon-btn" title="Chế độ xem lưới" aria-label="Xem lưới">
                                    <i class="bi bi-grid-fill"></i>
                                </button>
                                <button type="button" class="macos-icon-btn" title="Chia sẻ bộ sưu tập" aria-label="Chia sẻ">
                                    <i class="bi bi-box-arrow-up"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Row 2: Filter Tabs with Counters --}}
                        <div class="supaste-notch__tabs" role="tablist" id="shelf-filter-tabs">
                            <button type="button" class="tab is-active" role="tab" data-filter="all">Tất cả <span class="tab-count">3</span></button>
                            <button type="button" class="tab" role="tab" data-filter="deluxe">Deluxe <span class="tab-count">1</span></button>
                            <button type="button" class="tab" role="tab" data-filter="suite">Suite <span class="tab-count">1</span></button>
                            <button type="button" class="tab" role="tab" data-filter="presidential">Presidential <span class="tab-count">1</span></button>
                        </div>

                        {{-- Row 3: Exactly 3 Most Beautiful Rooms --}}
                        @php
                            $allRooms = isset($roomTypes) && $roomTypes->isNotEmpty() ? $roomTypes : (isset($featuredRooms) ? $featuredRooms : collect());
                            $topThreeRooms = collect();
                            $deluxe = $allRooms->first(fn($r) => !str_contains(mb_strtolower($r->type_name), 'vip') && !str_contains(mb_strtolower($r->type_name), 'suite') && !str_contains(mb_strtolower($r->type_name), 'gia đình'));
                            $suite = $allRooms->first(fn($r) => str_contains(mb_strtolower($r->type_name), 'suite') || str_contains(mb_strtolower($r->type_name), 'gia đình') || str_contains(mb_strtolower($r->type_name), 'triple'));
                            $vip = $allRooms->first(fn($r) => str_contains(mb_strtolower($r->type_name), 'vip') || str_contains(mb_strtolower($r->type_name), 'presidential'));
                            if ($deluxe) $topThreeRooms->push($deluxe);
                            if ($suite) $topThreeRooms->push($suite);
                            if ($vip) $topThreeRooms->push($vip);
                            if ($topThreeRooms->count() < 3) {
                                $topThreeRooms = $allRooms->take(3);
                            }
                        @endphp
                        @if($topThreeRooms->isNotEmpty())
                            <div class="supaste-shelf__cards" id="shelf-cards-container">
                                @foreach($topThreeRooms as $room)
                                    @php
                                        $nameLower = mb_strtolower($room->type_name);
                                        if (str_contains($nameLower, 'vip') || str_contains($nameLower, 'presidential')) {
                                            $category = 'presidential';
                                        } elseif (str_contains($nameLower, 'triple') || str_contains($nameLower, 'gia đình') || str_contains($nameLower, 'suite')) {
                                            $category = 'suite';
                                        } else {
                                            $category = 'deluxe';
                                        }
                                    @endphp
                                    <article class="supaste-room-card" data-room-category="{{ $category }}">
                                        <div class="supaste-room-card__img">
                                            <img src="{{ $room->image ?: config('room_images.' . $room->id . '.0', asset('images/rooms/default.jpg')) }}"
                                                 alt="{{ $room->type_name }} tại Posh Boutique"
                                                 loading="lazy"
                                                 onerror="this.onerror=null;this.src='{{ asset('images/rooms/default.jpg') }}';">
                                            <span class="supaste-room-card__badge">
                                                {{ $room->available_count ?? 2 }} phòng trống
                                            </span>
                                        </div>
                                        <div class="supaste-room-card__info">
                                            <h4>{{ $room->type_name }}</h4>
                                            <p>{{ number_format((float) $room->price, 0, ',', '.') }} đ <small>/ đêm</small></p>
                                            <a href="{{ route('rooms.detail', $room->id) }}" class="supaste-card-btn">
                                                Xem chi tiết phòng →
                                            </a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Concave corner fillet right --}}
                    <div class="supaste-notch__fillet supaste-notch__fillet--right" aria-hidden="true"></div>
                </div>

                {{-- Right Wing --}}
                <div class="supaste-window__wing supaste-window__wing--right">
                    <div class="supaste-window__status">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <i class="bi bi-wifi" aria-hidden="true"></i>
                        <span class="status-time">09:41</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3D Landscape Layer: Foreground Hill (In FRONT of the Glass Window, z-index: 4) --}}
    <div class="supaste-hero__fg" aria-hidden="true">
        <img src="{{ asset('images/home/supaste-hills-fg.webp') }}" alt="" class="supaste-hero__fg-img">
    </div>

    {{-- Smooth bottom fade into pure white for the next section (z-index: 5) --}}
    <div class="supaste-hero__fade" aria-hidden="true"></div>
</section>

{{-- =========================================================================
     OFFICIAL PARTNERS & AUTHENTIC PAYMENT LOGOS STRIP (90x90 PILLS)
     ========================================================================= --}}
<section class="supaste-section" aria-labelledby="partners-title">
    <div class="supaste-section__header">
        <h2 id="partners-title" class="supaste-section__title">Hỗ trợ thanh toán đa nền tảng</h2>
        <p class="supaste-section__desc">
            Tích hợp liền mạch với các ví điện tử hàng đầu, cổng thanh toán ngân hàng bảo mật và các đối tác du lịch quốc tế uy tín.
        </p>
    </div>

    <div class="supaste-marquee-container" aria-label="Băng chuyền phương thức thanh toán và đối tác chính hãng">
        <div class="supaste-marquee-track">
            {{-- Set 1 --}}
            <div class="supaste-marquee-content">
                {{-- MoMo --}}
                <div class="supaste-icon-pill" title="Ví MoMo">
                    <img src="{{ asset('images/payment-methods/momo.png') }}" alt="MoMo Logo" loading="lazy">
                    <span>MoMo</span>
                </div>

                {{-- VietQR --}}
                <div class="supaste-icon-pill" title="VietQR">
                    <img src="{{ asset('images/payment-methods/vietqr.png') }}" alt="VietQR Logo" loading="lazy">
                    <span>VietQR</span>
                </div>

                {{-- ZaloPay --}}
                <div class="supaste-icon-pill" title="ZaloPay">
                    <img src="{{ asset('images/payment-methods/zalopay.png') }}" alt="ZaloPay Logo" loading="lazy">
                    <span>ZaloPay</span>
                </div>

                {{-- VNPay --}}
                <div class="supaste-icon-pill" title="VNPay">
                    <img src="{{ asset('images/payment-methods/vnpay.png') }}" alt="VNPay Logo" loading="lazy">
                    <span>VNPay</span>
                </div>

                {{-- Visa --}}
                <div class="supaste-icon-pill" title="Visa">
                    <img src="{{ asset('images/partners/visa.png') }}" alt="Visa Logo" loading="lazy">
                    <span>Visa</span>
                </div>

                {{-- Mastercard --}}
                <div class="supaste-icon-pill" title="Mastercard">
                    <svg viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Mastercard Logo">
                        <circle cx="18" cy="16" r="10" fill="#EB001B"/>
                        <circle cx="30" cy="16" r="10" fill="#F79E1B"/>
                        <path d="M24 8.7a9.96 9.96 0 0 1 4 7.3 9.96 9.96 0 0 1-4 7.3 9.96 9.96 0 0 1-4-7.3c0-2.8 1.6-5.3 4-7.3z" fill="#FF5F00"/>
                    </svg>
                    <span>Mastercard</span>
                </div>

                {{-- Apple Pay --}}
                <div class="supaste-icon-pill" title="Apple Pay">
                    <img src="{{ asset('images/partners/apple-pay.png') }}" alt="Apple Pay Logo" loading="lazy">
                    <span>Apple Pay</span>
                </div>

                {{-- Booking.com --}}
                <div class="supaste-icon-pill" title="Booking.com">
                    <img src="{{ asset('images/partners/booking.png') }}" alt="Booking.com Logo" loading="lazy">
                    <span>Booking.com</span>
                </div>

                {{-- Agoda --}}
                <div class="supaste-icon-pill" title="Agoda">
                    <svg viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Agoda Logo">
                        <circle cx="12" cy="16" r="4.5" fill="#5392F9"/>
                        <circle cx="20" cy="16" r="4.5" fill="#88C140"/>
                        <circle cx="28" cy="16" r="4.5" fill="#F8B133"/>
                        <circle cx="36" cy="16" r="4.5" fill="#E84855"/>
                    </svg>
                    <span>Agoda</span>
                </div>
            </div>

            {{-- Set 2 (Duplicate for seamless infinite marquee loop) --}}
            <div class="supaste-marquee-content" aria-hidden="true">
                {{-- MoMo --}}
                <div class="supaste-icon-pill" title="Ví MoMo">
                    <img src="{{ asset('images/payment-methods/momo.png') }}" alt="MoMo Logo" loading="lazy">
                    <span>MoMo</span>
                </div>

                {{-- VietQR --}}
                <div class="supaste-icon-pill" title="VietQR">
                    <img src="{{ asset('images/payment-methods/vietqr.png') }}" alt="VietQR Logo" loading="lazy">
                    <span>VietQR</span>
                </div>

                {{-- ZaloPay --}}
                <div class="supaste-icon-pill" title="ZaloPay">
                    <img src="{{ asset('images/payment-methods/zalopay.png') }}" alt="ZaloPay Logo" loading="lazy">
                    <span>ZaloPay</span>
                </div>

                {{-- VNPay --}}
                <div class="supaste-icon-pill" title="VNPay">
                    <img src="{{ asset('images/payment-methods/vnpay.png') }}" alt="VNPay Logo" loading="lazy">
                    <span>VNPay</span>
                </div>

                {{-- Visa --}}
                <div class="supaste-icon-pill" title="Visa">
                    <img src="{{ asset('images/partners/visa.png') }}" alt="Visa Logo" loading="lazy">
                    <span>Visa</span>
                </div>

                {{-- Mastercard --}}
                <div class="supaste-icon-pill" title="Mastercard">
                    <svg viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Mastercard Logo">
                        <circle cx="18" cy="16" r="10" fill="#EB001B"/>
                        <circle cx="30" cy="16" r="10" fill="#F79E1B"/>
                        <path d="M24 8.7a9.96 9.96 0 0 1 4 7.3 9.96 9.96 0 0 1-4 7.3 9.96 9.96 0 0 1-4-7.3c0-2.8 1.6-5.3 4-7.3z" fill="#FF5F00"/>
                    </svg>
                    <span>Mastercard</span>
                </div>

                {{-- Apple Pay --}}
                <div class="supaste-icon-pill" title="Apple Pay">
                    <img src="{{ asset('images/partners/apple-pay.png') }}" alt="Apple Pay Logo" loading="lazy">
                    <span>Apple Pay</span>
                </div>

                {{-- Booking.com --}}
                <div class="supaste-icon-pill" title="Booking.com">
                    <img src="{{ asset('images/partners/booking.png') }}" alt="Booking.com Logo" loading="lazy">
                    <span>Booking.com</span>
                </div>

                {{-- Agoda --}}
                <div class="supaste-icon-pill" title="Agoda">
                    <svg viewBox="0 0 48 32" fill="none" xmlns="http://www.w3.org/2000/svg" aria-label="Agoda Logo">
                        <circle cx="12" cy="16" r="4.5" fill="#5392F9"/>
                        <circle cx="20" cy="16" r="4.5" fill="#88C140"/>
                        <circle cx="28" cy="16" r="4.5" fill="#F8B133"/>
                        <circle cx="36" cy="16" r="4.5" fill="#E84855"/>
                    </svg>
                    <span>Agoda</span>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- =========================================================================
     SUPASTE "BUILT FOR EVERYTHING" FEATURES GRID (CLEAN & ANTI-SLOP)
     ========================================================================= --}}
<section class="supaste-section" id="features" aria-labelledby="features-title">
    <div class="supaste-section__header">
        <h2 id="features-title" class="supaste-section__title">Thiết kế cho kỳ nghỉ trọn vẹn</h2>
        <p class="supaste-section__desc">
            Từ không gian phòng tĩnh lặng, ẩm thực phục vụ tận phòng đến các đặc quyền thư giãn cá nhân, Posh Boutique chăm chút từng khoảnh khắc trong kỳ nghỉ của bạn.
        </p>
    </div>

    <div class="supaste-features-grid">
        {{-- Card 1: Giấc ngủ sâu --}}
        <article class="supaste-feature-card">
            <div class="supaste-feature-card__fav" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 4v16"/>
                    <path d="M2 8h18a2 2 0 0 1 2 2v10"/>
                    <path d="M2 17h20"/>
                    <path d="M6 8v9"/>
                </svg>
            </div>
            <h4>Không gian tĩnh lặng &amp; Nệm 5 sao</h4>
            <p>Hệ thống nệm King-size cao cấp cùng công nghệ kính tiêu âm đa lớp mang lại không gian tĩnh lặng tuyệt đối cho từng giấc ngủ sâu.</p>
        </article>

        {{-- Card 2: Ẩm thực tinh hoa --}}
        <article class="supaste-feature-card">
            <div class="supaste-feature-card__fav" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 8h1a4 4 0 0 1 0 8h-1"/>
                    <path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/>
                    <line x1="6" y1="1" x2="6" y2="4"/>
                    <line x1="10" y1="1" x2="10" y2="4"/>
                    <line x1="14" y1="1" x2="14" y2="4"/>
                </svg>
            </div>
            <h4>Buffet sáng &amp; In-room Dining 24/7</h4>
            <p>Thực đơn đa dạng tinh hoa ẩm thực Á - Âu chuẩn vị được chế biến từ các bếp trưởng quốc tế, phục vụ tận phòng theo yêu cầu.</p>
        </article>

        {{-- Card 3: Tiện nghi riêng tư --}}
        <article class="supaste-feature-card">
            <div class="supaste-feature-card__fav" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="5" y="2" width="14" height="20" rx="3"/>
                    <line x1="12" y1="18" x2="12.01" y2="18"/>
                </svg>
            </div>
            <h4>Smart Check-in &amp; Quản trị số</h4>
            <p>Thủ tục nhận phòng không chạm, điều khiển thiết bị phòng thông minh và theo dõi lịch trình lưu trú tiện lợi ngay trên điện thoại.</p>
        </article>

        {{-- Card 4: Thư giãn đỉnh cao --}}
        <article class="supaste-feature-card">
            <div class="supaste-feature-card__fav" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>
                    <path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>
                    <path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>
                </svg>
            </div>
            <h4>Hồ bơi vô cực &amp; Spa thảo mộc</h4>
            <p>Tận hưởng làn nước mát ngắm hoàng hôn thành phố cùng các liệu trình massage thảo dược tự nhiên giúp phục hồi năng lượng.</p>
        </article>
    </div>
</section>

{{-- =========================================================================
     TESTIMONIALS / GUEST REVIEWS GRID
     ========================================================================= --}}
<section class="supaste-section" aria-labelledby="reviews-title">
    <div class="supaste-section__header">
        <h2 id="reviews-title" class="supaste-section__title">Những chia sẻ chân thực</h2>
        <p class="supaste-section__desc">
            Lắng nghe cảm nhận từ những vị khách đã dừng chân và tận hưởng kỳ nghỉ dưỡng tại Posh Boutique.
        </p>
    </div>

    @if($latestReviews->isNotEmpty())
        <div class="review-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
            @foreach($latestReviews->take(3) as $review)
                <article class="review-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                    <div class="review-card__rating" style="color: #f59e0b; font-size: 16px; margin-bottom: 12px;" aria-label="Đánh giá {{ $review->rating }} trên 5 sao">
                        {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', max(0, 5 - $review->rating)) }}
                    </div>
                    <blockquote style="color: #334155; font-size: 14.5px; line-height: 1.6; margin: 0 0 18px 0; font-style: italic;">
                        “{{ $review->comment ?: 'Trải nghiệm nghỉ dưỡng tuyệt vời, nhân viên chuyên nghiệp và phòng nghỉ cực kỳ sạch sẽ, ấm cúng.' }}”
                    </blockquote>
                    <footer style="display: flex; flex-direction: column; gap: 2px;">
                        <strong style="color: #0f172a; font-size: 14px;">{{ $review->user?->fullname ?? 'Khách lưu trú' }}</strong>
                        <span style="color: #64748b; font-size: 12px;">{{ $review->roomType?->type_name ?? 'Posh Boutique' }}</span>
                    </footer>
                </article>
            @endforeach
        </div>
    @else
        <div class="empty-state">
            <p>Đánh giá từ khách lưu trú sẽ xuất hiện tại đây.</p>
        </div>
    @endif
</section>

{{-- =========================================================================
     SUPASTE PRICING CARDS (ONE PRICE. LIFETIME ACCESS STYLE)
     ========================================================================= --}}
<section class="supaste-pricing-section" aria-labelledby="pricing-title">
    <div class="supaste-pricing-section__inner">
        <div class="supaste-section__header">
            <h2 id="pricing-title" class="supaste-section__title">Lựa chọn hạng phòng phù hợp</h2>
            <p class="supaste-section__desc">
                Giá trọn gói niêm yết rõ ràng, không phụ phí ẩn, tích hợp toàn bộ tiện ích nghỉ dưỡng cao cấp.
            </p>
        </div>

        @php
            $deluxeType = isset($roomTypes) ? ($roomTypes->firstWhere('id', 2) ?? $roomTypes->first()) : null;
            $suiteType = isset($roomTypes) ? ($roomTypes->firstWhere('id', 4) ?? ($roomTypes->count() > 2 ? $roomTypes->get(2) : null)) : null;
            $presidentialType = isset($roomTypes) ? ($roomTypes->firstWhere('id', 5) ?? $roomTypes->last()) : null;

            $deluxeName = $deluxeType ? $deluxeType->type_name : 'Phòng Đôi Tiêu Chuẩn';
            $deluxePrice = $deluxeType ? number_format((float)$deluxeType->price, 0, ',', '.') : '650.000';
            $deluxeUrl = $deluxeType ? route('rooms.detail', $deluxeType->id) : route('rooms.index');

            $suiteName = $suiteType ? $suiteType->type_name : 'Phòng Gia Đình';
            $suitePrice = $suiteType ? number_format((float)$suiteType->price, 0, ',', '.') : '1.200.000';
            $suiteUrl = $suiteType ? route('rooms.detail', $suiteType->id) : route('rooms.index');

            $presidentialName = $presidentialType ? $presidentialType->type_name : 'Phòng VIP';
            $presidentialPrice = $presidentialType ? number_format((float)$presidentialType->price, 0, ',', '.') : '4.000.000';
            $presidentialUrl = $presidentialType ? route('rooms.detail', $presidentialType->id) : route('rooms.index');
        @endphp

        <div class="supaste-pricing-grid">
            {{-- Card 1: Hạng Tiêu Chuẩn / Deluxe từ Database --}}
            <article class="supaste-price-card" data-reveal>
                <h3>{{ $deluxeName }}</h3>
                <div class="supaste-price-card__rate">{{ $deluxePrice }} đ <span>/ đêm</span></div>
                <div class="supaste-price-card__tax-note">Giá lưu trú mỗi đêm</div>
                <ul class="supaste-price-card__features">
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Giường Queen-size 1m8 cao cấp</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Ban công riêng ngắm view thành phố</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Bữa sáng buffet tiêu chuẩn Á - Âu</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Wi-Fi tốc độ cao &amp; Smart TV 55"</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Trà, cà phê &amp; nước khoáng miễn phí</span></li>
                </ul>
                <a href="{{ $deluxeUrl }}" class="supaste-price-btn">
                    <span>Đặt {{ $deluxeName }}</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </article>

            {{-- Card 2: Hạng Suite / Gia Đình từ Database (Featured) --}}
            <article class="supaste-price-card is-featured" data-reveal>
                <h3>{{ $suiteName }}</h3>
                <div class="supaste-price-card__rate">{{ $suitePrice }} đ <span>/ đêm</span></div>
                <div class="supaste-price-card__tax-note">Đã bao gồm bữa sáng &amp; tiện ích VIP</div>

                <ul class="supaste-price-card__features">
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Giường King-size 2m thượng hạng</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Phòng khách &amp; quầy bar mini cao cấp</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Bồn tắm sục Jacuzzi ngắm hoàng hôn</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Quản gia cá nhân hỗ trợ 24/7</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Miễn phí check-out trễ đến 14:00</span></li>
                </ul>
                <a href="{{ $suiteUrl }}" class="supaste-price-btn">
                    <span>Đặt {{ $suiteName }}</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </article>

            {{-- Card 3: Hạng VIP / Thượng đỉnh từ Database --}}
            <article class="supaste-price-card" data-reveal>
                <h3>{{ $presidentialName }}</h3>
                <div class="supaste-price-card__rate">{{ $presidentialPrice }} đ <span>/ đêm</span></div>
                <div class="supaste-price-card__tax-note">Trọn gói dịch vụ thượng lưu &amp; đưa đón VIP</div>
                <ul class="supaste-price-card__features">
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>2 phòng ngủ master + phòng họp riêng</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Hồ bơi riêng tại ban công tầng thượng</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Bữa tối Fine Dining 5 món tại phòng</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Xe Limousine đưa đón sân bay 2 chiều</span></li>
                    <li><i class="bi bi-check2-circle" aria-hidden="true"></i> <span>Đặc quyền VIP Lounge không giới hạn</span></li>
                </ul>
                <a href="{{ $presidentialUrl }}" class="supaste-price-btn">
                    <span>Đặt {{ $presidentialName }}</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </article>
        </div>

        <p class="supaste-pricing-section__footer-note">
            <i class="bi bi-shield-check" style="color: #60a5fa; font-size: 15px; vertical-align: -1px; margin-right: 6px;" aria-hidden="true"></i>
            Cam kết giá minh bạch từ Posh Boutique · Thanh toán bảo mật qua VNPAY / VietQR / Thẻ tín dụng quốc tế · Miễn phí hủy trước 48 giờ
        </p>
    </div>
</section>

{{-- =========================================================================
     SUPASTE FAQ ACCORDION (INTERACTIVE)
     ========================================================================= --}}
<section class="supaste-section" aria-labelledby="faq-title">
    <div class="supaste-section__header">
        <h2 id="faq-title" class="supaste-section__title">Giải đáp mọi thắc mắc</h2>
        <p class="supaste-section__desc">
            Những thông tin cần biết để kỳ nghỉ của bạn diễn ra suôn sẻ và trọn vẹn nhất.
        </p>
    </div>

    <div class="supaste-faq-list">
        <div class="supaste-faq-item">
            <details open>
                <summary>Giờ nhận phòng (check-in) và trả phòng (check-out) tại Posh Boutique là khi nào?</summary>
                <p>Khách sạn nhận phòng từ 14:00 và trả phòng trước 12:00 trưa. Nếu bạn muốn nhận phòng sớm hoặc trả phòng muộn, vui lòng liên hệ trước với bộ phận lễ tân để được hỗ trợ sắp xếp theo tình trạng phòng thực tế.</p>
            </details>
        </div>

        <div class="supaste-faq-item">
            <details>
                <summary>Chính sách hủy đặt phòng và hoàn tiền được áp dụng như thế nào?</summary>
                <p>Quý khách được miễn phí hủy phòng trước 48 giờ so với giờ nhận phòng tiêu chuẩn. Tiền đặt cọc sẽ được hoàn lại theo đúng phương thức thanh toán ban đầu của quý khách trong vòng 3-5 ngày làm việc.</p>
            </details>
        </div>

        <div class="supaste-faq-item">
            <details>
                <summary>Khách sạn hỗ trợ những phương thức thanh toán nào?</summary>
                <p>Posh Boutique hỗ trợ thanh toán trực tuyến qua Ví MoMo, ZaloPay, VietQR ngân hàng và cổng VNPay.</p>
            </details>
        </div>

        <div class="supaste-faq-item">
            <details>
                <summary>Khách sạn có cung cấp dịch vụ đưa đón sân bay không?</summary>
                <p>Có. Posh Boutique cung cấp dịch vụ xe đưa đón cao cấp từ sân bay về khách sạn và ngược lại. Hạng phòng Presidential Suite được miễn phí dịch vụ xe Limousine 2 chiều.</p>
            </details>
        </div>

        <div class="supaste-faq-item">
            <details>
                <summary>Trẻ em đi cùng có phát sinh thêm phụ phí không?</summary>
                <p>Trẻ em dưới 6 tuổi được miễn phí lưu trú khi ngủ chung giường với bố mẹ (tối đa 1 trẻ/phòng). Trẻ từ 6-11 tuổi phụ thu bữa sáng buffet theo tiêu chuẩn khách sạn.</p>
            </details>
        </div>
    </div>
</section>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabsContainer = document.getElementById('shelf-filter-tabs');
    const cardsContainer = document.getElementById('shelf-cards-container');
    if (!tabsContainer || !cardsContainer) return;

    const tabs = tabsContainer.querySelectorAll('.tab');
    const cards = cardsContainer.querySelectorAll('.supaste-room-card');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((t) => t.classList.remove('is-active'));
            tab.classList.add('is-active');

            const filter = tab.dataset.filter;
            cards.forEach((card) => {
                const category = card.dataset.roomCategory;
                const match = filter === 'all' || category === filter;
                if (match) {
                    card.style.display = 'flex';
                    if (window.gsap) {
                        gsap.fromTo(card,
                            { autoAlpha: 0, scale: 0.96, y: 10 },
                            { autoAlpha: 1, scale: 1, y: 0, duration: 0.32, ease: 'power2.out', clearProps: 'transform' }
                        );
                    } else {
                        card.style.opacity = '1';
                    }
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
});
</script>
@endpush

@endsection
