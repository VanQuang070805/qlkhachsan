@extends('client.layouts.app')

@section('title', 'Giới thiệu & Liên hệ · Rosaliza Hotel HaNoi')

@section('content')
<div class="melia-page-shell melia-contact-shell">

    {{-- =========================================================================
         1. HERO SECTION — EDITORIAL LUXURY ARRIVAL
         ========================================================================= --}}
    <section class="melia-page-hero" aria-labelledby="contact-title">
        <div class="melia-page-hero__inner">
            <span class="melia-page-hero__tag">
                Rosaliza Heritage &amp; Hospitality &bull; Hoàn Kiếm, Hanoi
            </span>

            <h1 id="contact-title" class="melia-page-hero__title" data-rb-split="words,chars">
                A thoughtful sanctuary, <em>close to everything.</em>
            </h1>

            <p class="melia-page-hero__desc" data-rb-blur="words">
                Một chốn nghỉ dưỡng thanh lịch, riêng tư nép mình tại phố Trần Quốc Toản, nơi bạn tìm về sự tĩnh lặng trọn vẹn và đủ gần để chạm tới mọi nhịp sống văn hóa di sản Hà Nội.
            </p>

            <div class="melia-page-hero__actions">
                <a href="#contact-details" class="melia-btn-primary" data-rb-magnet>
                    <span>Liên hệ với Trợ lý</span>
                    <i class="bi bi-arrow-down"></i>
                </a>
                <a href="#hotel-story" class="melia-btn-secondary" data-rb-magnet>
                    <span>Câu chuyện Rosaliza</span>
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    <div class="melia-page-container">

        {{-- =========================================================================
             2. OUR HERITAGE & STORY SECTION
             ========================================================================= --}}
        <section class="melia-contact-story" id="hotel-story" aria-labelledby="story-title">
            <div class="melia-story-grid">
                <div class="melia-story-content">
                    <span class="melia-sec-tag">Our Story &amp; Philosophy</span>
                    <h2 id="story-title" class="melia-sec-title">
                        Quiet by design, Warm at heart
                    </h2>
                    <p>
                        Rosaliza Hotel HaNoi được tạo nên cho những hành trình tìm kiếm sự cân bằng và thanh tao. Tọa lạc tại số 5B Trần Quốc Toản, giữa những hàng cây xanh rợp bóng mát của khu phố di sản Pháp cổ, khách sạn là sự giao thoa tinh tế giữa kiến trúc Đông Dương sang trọng và phong cách sống Quiet Luxury đương đại.
                    </p>
                    <p>
                        Tại Rosaliza, chúng tôi tin rằng sự xa xỉ đích thực không nằm ở những ồn ào khoa trương, mà hiện diện trong từng khoảnh khắc an yên: từ giấc ngủ sâu trên chiếc giường êm ái, hương trà sen thoang thoảng mỗi sớm mai, đến sự tiếp đón nồng hậu và tinh tế của đội ngũ quản gia.
                    </p>

                    <div class="melia-story-facts" aria-label="Thông tin nổi bật Rosaliza Hotel HaNoi">
                        <div class="melia-fact-item">
                            <span class="melia-fact-num">Boutique</span>
                            <span class="melia-fact-label">Khách sạn</span>
                        </div>
                        <div class="melia-fact-item">
                            <span class="melia-fact-num">Hà Nội</span>
                            <span class="melia-fact-label">Khu vực Hoàn Kiếm</span>
                        </div>
                        <div class="melia-fact-item">
                            <span class="melia-fact-num">Hỗ trợ</span>
                            <span class="melia-fact-label">Thông tin đặt phòng</span>
                        </div>
                        <div class="melia-fact-item">
                            <span class="melia-fact-num">Nghỉ ngơi</span>
                            <span class="melia-fact-label">Không gian lưu trú</span>
                        </div>
                    </div>
                </div>

                <div class="melia-story-visual">
                    <img src="{{ asset('images/melia/hero-hanoi-suite.jpg') }}" alt="Không gian phòng nghỉ dưỡng thanh lịch tại Rosaliza Hotel HaNoi" loading="lazy">
                </div>
            </div>
        </section>

        {{-- =========================================================================
             3. CORE HOSPITALITY VALUES
             ========================================================================= --}}
        <section class="melia-values-section" aria-labelledby="values-title">
            <div class="melia-page-container" style="padding: 0;">
                <div class="melia-sec-header melia-sec-header--center">
                    <span class="melia-sec-tag">Our Values</span>
                    <h2 id="values-title" class="melia-sec-title">Simply considered, Truly caring</h2>
                    <p class="melia-sec-subtitle">
                        Ba giá trị cốt lõi chúng tôi trân trọng gìn giữ trong mỗi kỳ lưu trú của quý khách.
                    </p>
                </div>

                <div class="melia-values-grid">
                    <article class="melia-value-card">
                        <span class="melia-value-index">01</span>
                        <h3 class="melia-value-title">Room to breathe</h3>
                        <p class="melia-value-desc">
                            Không gian phòng yên tĩnh với hệ thống cách âm tiêu chuẩn quốc tế và đệm cao cấp, để bạn thật sự nghỉ ngơi sâu lắng sau một ngày dạo bước phố cổ.
                        </p>
                    </article>

                    <article class="melia-value-card">
                        <span class="melia-value-index">02</span>
                        <h3 class="melia-value-title">Care in detail</h3>
                        <p class="melia-value-desc">
                            Mọi tiện nghi từ gối lụa tơ tằm, phòng tắm đá cẩm thạch đến hương tinh dầu thảo mộc sen Tây Hồ đều được chăm chút tỉ mỉ cho trải nghiệm trọn vẹn.
                        </p>
                    </article>

                    <article class="melia-value-card">
                        <span class="melia-value-index">03</span>
                        <h3 class="melia-value-title">Dịch vụ tận tâm</h3>
                        <p class="melia-value-desc">
                            Đội ngũ Lễ tân &amp; Quản gia luôn sẵn sàng hỗ trợ 24/7 từ đặt vé Nhà hát Lớn, gợi ý ẩm thực phố cổ đến xe limousine đưa đón sân bay Nội Bài.
                        </p>
                    </article>
                </div>
            </div>
        </section>

        {{-- =========================================================================
             4. DIRECT CHANNELS & INTERACTIVE INQUIRY FORM
             ========================================================================= --}}
        <section class="melia-contact-section" id="contact-details" aria-labelledby="contact-details-title">
            <div class="melia-contact-split">
                {{-- Left: Direct Communication Channels --}}
                <div>
                    <span class="melia-sec-tag">Liên hệ &amp; Hỗ trợ</span>
                    <h2 id="contact-details-title" class="melia-sec-title">Always here for you</h2>
                    <p class="melia-sec-subtitle">
                        Gọi điện, gửi email hoặc ghé thăm chúng tôi tại phố Trần Quốc Toản, quận Hoàn Kiếm.
                    </p>

                    <div class="melia-channels-list">
                        {{-- Phone --}}
                        <a href="tel:02439446699" class="melia-channel-card" aria-label="Gọi hotline Rosaliza Hotel 024 3944 6699">
                            <div class="melia-channel-icon">
                                <i class="bi bi-telephone"></i>
                            </div>
                            <div class="melia-channel-info">
                                <span class="melia-channel-label">Điện thoại đặt phòng</span>
                                <span class="melia-channel-val">(024) 3944 6699</span>
                                <span class="melia-channel-hint">Hỗ trợ nhanh qua cuộc gọi trực tiếp</span>
                            </div>
                            <div class="melia-channel-arrow">
                                <i class="bi bi-arrow-up-right"></i>
                            </div>
                        </a>

                        {{-- Email --}}
                        <a href="mailto:reservations@rosalizahotel.com" class="melia-channel-card" aria-label="Gửi email đến reservations@rosalizahotel.com">
                            <div class="melia-channel-icon">
                                <i class="bi bi-envelope"></i>
                            </div>
                            <div class="melia-channel-info">
                                <span class="melia-channel-label">Email hỗ trợ</span>
                                <span class="melia-channel-val">reservations@rosalizahotel.com</span>
                                <span class="melia-channel-hint">Gửi yêu cầu chi tiết qua email</span>
                            </div>
                            <div class="melia-channel-arrow">
                                <i class="bi bi-arrow-up-right"></i>
                            </div>
                        </a>

                        {{-- Airport Desk --}}
                        <div class="melia-channel-card" style="cursor: default;">
                            <div class="melia-channel-icon">
                                <i class="bi bi-airplane"></i>
                            </div>
                            <div class="melia-channel-info">
                                <span class="melia-channel-label">Đưa Đón Sân Bay Nội Bài</span>
                                <span class="melia-channel-val">Thông tin đưa đón</span>
                                <span class="melia-channel-hint">Liên hệ để được tư vấn</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right: Interactive Contact & Inquiry Form --}}
                <div class="melia-form-card">
                    <span class="melia-sec-tag">Direct Inquiry</span>
                    <h3 style="font-family: 'Inter', -apple-system, sans-serif; font-weight: 700; font-size: 1.35rem; margin: 0 0 8px 0; color: var(--melia-text);">Gửi thư liên hệ</h3>
                    <p style="font-size: 14px; color: var(--melia-text-muted); margin-bottom: 24px;">
                        Biểu mẫu đang chưa được kết nối. Vui lòng sử dụng thông tin điện thoại hoặc email ở bên cạnh để liên hệ.
                    </p>

                    <form id="contact-inquiry-form" onsubmit="return false;">
                        <fieldset disabled aria-label="Biểu mẫu liên hệ chưa khả dụng">
                        <div class="melia-form-grid">
                            <div class="melia-form-grid--2col melia-form-grid" style="gap: 16px;">
                                <div class="melia-form-group">
                                    <label for="contact-name">Họ và tên *</label>
                                    <input type="text" id="contact-name" class="melia-form-control" placeholder="Nguyễn Văn A" required>
                                </div>
                                <div class="melia-form-group">
                                    <label for="contact-phone">Số điện thoại *</label>
                                    <input type="tel" id="contact-phone" class="melia-form-control" placeholder="0912 345 678" required>
                                </div>
                            </div>

                            <div class="melia-form-group">
                                <label for="contact-email">Địa chỉ Email *</label>
                                <input type="email" id="contact-email" class="melia-form-control" placeholder="email@example.com" required>
                            </div>

                            <div class="melia-form-group">
                                <label for="contact-service">Dịch vụ quan tâm</label>
                                <select id="contact-service" class="melia-form-control">
                                    <option value="room">Tư vấn đặt phòng nghỉ dưỡng</option>
                                    <option value="skyline">Đặt bàn Skyline Rooftop Bar</option>
                                    <option value="spa">Liệu trình Lotus Spa &amp; Wellness</option>
                                    <option value="honeymoon">Gói tuần trăng mật / Kỷ niệm</option>
                                    <option value="airport">Đưa đón sân bay Nội Bài</option>
                                    <option value="other">Yêu cầu khác</option>
                                </select>
                            </div>

                            <div class="melia-form-group">
                                <label for="contact-message">Nội dung tin nhắn / Ghi chú</label>
                                <textarea id="contact-message" class="melia-form-control" placeholder="Quý khách có yêu cầu đặc biệt về số lượng khách, ngày lưu trú hoặc dịch vụ kèm theo?"></textarea>
                            </div>

                            <button type="submit" class="melia-btn-primary" style="width: 100%; padding: 14px; font-size: 14px;">
                                <span>Gửi yêu cầu hỗ trợ</span>
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                        </fieldset>
                    </form>
                </div>
            </div>
        </section>

        {{-- =========================================================================
             5. LOCATION & GOOGLE MAPS SECTION
             ========================================================================= --}}
        <section class="melia-map-card" aria-labelledby="location-title">
            <div class="melia-map-header">
                <div>
                    <h4 id="location-title">
                        <i class="bi bi-geo-alt-fill" style="color: var(--melia-gold);"></i>
                        5B Trần Quốc Toản, Hàng Bài, Hoàn Kiếm, Hà Nội
                    </h4>
                    <span style="font-size: 12.5px; color: var(--melia-text-muted);">
                        Nhận phòng từ 14:00 &bull; Trả phòng trước 12:00 trưa &bull; Lễ tân hỗ trợ 24 giờ
                    </span>
                </div>
                <a href="https://maps.google.com/?q=5B+Tran+Quoc+Toan+Hoan+Kiem+Hanoi" target="_blank" rel="noopener noreferrer" class="melia-btn-secondary" style="padding: 8px 18px; font-size: 12px;">
                    <i class="bi bi-map"></i>
                    <span>Mở chỉ đường Google Maps</span>
                </a>
            </div>
            <div class="melia-map-frame">
                <iframe title="Bản đồ vị trí Rosaliza Hotel HaNoi tại 5B Trần Quốc Toản, Hoàn Kiếm, Hà Nội"
                        src="https://maps.google.com/maps?q=5B%20Tran%20Quoc%20Toan,%20Hoan%20Kiem,%20Hanoi&t=&z=16&ie=UTF8&iwloc=&output=embed"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        allowfullscreen></iframe>
            </div>
        </section>

    </div>
</div>
@endsection
