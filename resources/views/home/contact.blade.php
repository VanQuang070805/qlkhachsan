@extends('client.layouts.app')

@section('title', 'Giới thiệu & Liên hệ · Rosaliza Hotel Hà Nội')

@section('content')
<div class="melia-page-shell melia-contact-shell">

    {{-- 1. HERO SECTION --}}
    <section class="melia-page-hero" aria-labelledby="contact-title">
        <div class="melia-page-hero__inner">
            <span class="melia-page-hero__tag">
                ROSALIZA HOTEL · HOÀN KIẾM, HÀ NỘI
            </span>

            <h1 id="contact-title" class="melia-page-hero__title">
                Chốn dừng chân tĩnh lặng giữa lòng phố cổ Hà Nội
            </h1>

            <p class="melia-page-hero__desc">
                Nép mình tại số 5B Trần Quốc Toản dưới tán cây xanh khu phố Pháp cổ, Rosaliza Hotel mang đến không gian nghỉ ngơi riêng tư, yên ả và kết nối thuận tiện tới từng điểm hẹn văn hóa Hoàn Kiếm.
            </p>

            <div class="melia-page-hero__actions">
                <a href="{{ route('rooms.index') }}" class="melia-btn-primary">
                    <span>Khám phá hạng phòng</span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="#contact-details" class="melia-btn-secondary">
                    <span>Kết nối Lễ tân 24/7</span>
                    <i class="bi bi-telephone" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

    <div class="melia-page-container">

        {{-- 2. CÂU CHUYỆN & DẤU ẤN KIẾN TRÚC --}}
        <section class="melia-contact-story" id="hotel-story" aria-labelledby="story-title">
            <div class="melia-story-grid">
                <div class="melia-story-content">
                    <span class="melia-sec-tag">CÂU CHUYỆN ROSALIZA</span>
                    <h2 id="story-title" class="melia-sec-title">
                        Tinh giản trong thiết kế, ấm áp trong từng chi tiết
                    </h2>
                    <p>
                        Rosaliza Hotel được kiến tạo dành cho những du khách trân trọng sự tĩnh tại và chỉn chu. Giữa nhịp sống sôi động của quận Hoàn Kiếm, mỗi căn phòng tại số 5B Trần Quốc Toản được chăm chút với ánh sáng tự nhiên dịu nhẹ, vật liệu gỗ ấm, hệ cửa cách âm tiêu chuẩn và bộ chăn ga cao cấp.
                    </p>
                    <p>
                        Từ thủ tục đặt cọc trực tuyến minh bạch, nhận phòng nhanh bằng mã QR điện tử đến sự hỗ trợ 24/7 của đội ngũ Lễ tân và trợ lý thông minh Pico, mọi trải nghiệm đều hướng tới sự nhẹ nhàng, riêng tư và trọn vẹn.
                    </p>

                    <div class="melia-story-facts" aria-label="Thông tin vận hành Rosaliza Hotel">
                        <div class="melia-fact-item">
                            <span class="melia-fact-num">5B</span>
                            <span class="melia-fact-label">Trần Quốc Toản, Hoàn Kiếm</span>
                        </div>
                        <div class="melia-fact-item">
                            <span class="melia-fact-num">14:00</span>
                            <span class="melia-fact-label">Nhận phòng trước 18:00</span>
                        </div>
                        <div class="melia-fact-item">
                            <span class="melia-fact-num">50%</span>
                            <span class="melia-fact-label">Đặt cọc giữ phòng online</span>
                        </div>
                        <div class="melia-fact-item">
                            <span class="melia-fact-num">24/7</span>
                            <span class="melia-fact-label">Lễ tân &amp; Trợ lý Pico</span>
                        </div>
                    </div>
                </div>

                <div class="melia-story-visual">
                    <img src="{{ asset('images/melia/hero-hanoi-suite.jpg') }}" alt="Không gian phòng nghỉ thanh lịch tại Rosaliza Hotel Hà Nội" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/rooms/1.jpg') }}'">
                </div>
            </div>
        </section>

        {{-- 3. BA GIÁ TRỊ CỐT LÕI --}}
        <section class="melia-values-section melia-values-section--boxed" aria-labelledby="values-title">
            <div class="melia-sec-header melia-sec-header--center">
                <span class="melia-sec-tag">GIÁ TRỊ LƯU TRÚ</span>
                <h2 id="values-title" class="melia-sec-title">Ba chuẩn mực gìn giữ trong mỗi kỳ nghỉ</h2>
                <p class="melia-sec-subtitle">
                    Mỗi chi tiết vận hành tại Rosaliza Hotel đều đặt sự thư thái và an tâm của quý khách làm trọng tâm.
                </p>
            </div>

            <div class="melia-values-grid">
                <article class="melia-value-card">
                    <span class="melia-value-index">01</span>
                    <h3 class="melia-value-title">Không gian nghỉ sâu</h3>
                    <p class="melia-value-desc">
                        Hệ thống cách âm hai lớp, nệm nâng đỡ êm ái và ánh sáng ấm dịu giúp bạn tái tạo năng lượng trọn vẹn sau ngày dài khám phá Hà Nội.
                    </p>
                </article>

                <article class="melia-value-card">
                    <span class="melia-value-index">02</span>
                    <h3 class="melia-value-title">Quy trình minh bạch</h3>
                    <p class="melia-value-desc">
                        Giá phòng niêm yết rõ ràng theo thời gian thực, giữ phòng trực tuyến với mức cọc 50% và cấp thẻ QR nhận phòng điện tử ngay sau khi xác nhận.
                    </p>
                </article>

                <article class="melia-value-card">
                    <span class="melia-value-index">03</span>
                    <h3 class="melia-value-title">Đồng hành tận tâm 24/7</h3>
                    <p class="melia-value-desc">
                        Đội ngũ Lễ tân tại sảnh cùng trợ lý trực tuyến Pico luôn sẵn sàng giải đáp lịch trình, ẩm thực phố cổ và mọi nhu cầu trong suốt kỳ lưu trú.
                    </p>
                </article>
            </div>
        </section>

        {{-- 4. KÊNH LIÊN HỆ TRỰC TIẾP & CẨM NANG LƯU TRÚ --}}
        <section class="melia-contact-section" id="contact-details" aria-labelledby="contact-details-title">
            <div class="melia-contact-split">
                {{-- Cột trái: Kênh kết nối trực tiếp --}}
                <div>
                    <span class="melia-sec-tag">LIÊN HỆ &amp; HỖ TRỢ</span>
                    <h2 id="contact-details-title" class="melia-sec-title">Kết nối trực tiếp cùng Rosaliza Hotel</h2>
                    <p class="melia-sec-subtitle">
                        Chọn kênh liên lạc thuận tiện nhất để được hỗ trợ đặt phòng, xác nhận giao dịch hoặc tư vấn kỳ nghỉ tại Hà Nội.
                    </p>

                    <div class="melia-channels-list">
                        <a href="tel:02438289999" class="melia-channel-card" aria-label="Gọi hotline Rosaliza Hotel 024 3828 9999">
                            <div class="melia-channel-icon">
                                <i class="bi bi-telephone" aria-hidden="true"></i>
                            </div>
                            <div class="melia-channel-info">
                                <span class="melia-channel-label">Hotline Lễ tân &amp; Đặt phòng</span>
                                <span class="melia-channel-val">024 3828 9999</span>
                                <span class="melia-channel-hint">Tiếp nhận cuộc gọi 24/7 cho mọi yêu cầu khẩn</span>
                            </div>
                            <div class="melia-channel-arrow">
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </div>
                        </a>

                        <a href="mailto:contact@rosalizahotel.vn" class="melia-channel-card" aria-label="Gửi email đến contact@rosalizahotel.vn">
                            <div class="melia-channel-icon">
                                <i class="bi bi-envelope" aria-hidden="true"></i>
                            </div>
                            <div class="melia-channel-info">
                                <span class="melia-channel-label">Thư điện tử hỗ trợ</span>
                                <span class="melia-channel-val">contact@rosalizahotel.vn</span>
                                <span class="melia-channel-hint">Hỗ trợ đoàn khách, xuất chứng từ và yêu cầu riêng</span>
                            </div>
                            <div class="melia-channel-arrow">
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </div>
                        </a>

                        <button type="button" class="melia-channel-card melia-channel-card--btn" onclick="if (typeof window.openRoyalChat === 'function') window.openRoyalChat();">
                            <div class="melia-channel-icon">
                                <i class="bi bi-chat-dots" aria-hidden="true"></i>
                            </div>
                            <div class="melia-channel-info">
                                <span class="melia-channel-label">Trợ lý kỳ nghỉ trực tuyến</span>
                                <span class="melia-channel-val">Trò chuyện cùng Pico AI</span>
                                <span class="melia-channel-hint">Tra cứu phòng trống, giá phòng và chính sách tức thì</span>
                            </div>
                            <div class="melia-channel-arrow">
                                <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
                            </div>
                        </button>
                    </div>
                </div>

                {{-- Cột phải: Cẩm nang lưu trú & Khoảng cách di chuyển --}}
                <div class="melia-form-card rosaliza-stay-guide">
                    <span class="melia-sec-tag">CẨM NANG LƯU TRÚ</span>
                    <h3 class="rosaliza-stay-guide__title">Quy định nhận phòng &amp; Vị trí kết nối</h3>
                    <p class="rosaliza-stay-guide__desc">
                        Thông tin thiết yếu giúp quý khách chủ động sắp xếp thời gian di chuyển và làm thủ tục tại Rosaliza Hotel.
                    </p>

                    <div class="rosaliza-policy-list">
                        <div class="rosaliza-policy-row">
                            <div class="rosaliza-policy-icon"><i class="bi bi-clock" aria-hidden="true"></i></div>
                            <div>
                                <strong>Giờ nhận phòng: 14:00 - trước 18:00</strong>
                                <p>Đơn đặt chưa nhận phòng sau 18:00 sẽ tự động hủy theo quy định giữ phòng trong ngày.</p>
                            </div>
                        </div>
                        <div class="rosaliza-policy-row">
                            <div class="rosaliza-policy-icon"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></div>
                            <div>
                                <strong>Giờ trả phòng: Trước 12:00 trưa</strong>
                                <p>Hỗ trợ gia hạn theo giờ hoặc theo ngày trực tiếp trên trang Lịch sử đặt phòng khi phòng còn trống.</p>
                            </div>
                        </div>
                        <div class="rosaliza-policy-row">
                            <div class="rosaliza-policy-icon"><i class="bi bi-qr-code-scan" aria-hidden="true"></i></div>
                            <div>
                                <strong>Đặt cọc trực tuyến 50%</strong>
                                <p>Thanh toán nhanh qua VietQR Napas 247, MoMo, ZaloPay hoặc VNPay; 50% còn lại thanh toán tại quầy.</p>
                            </div>
                        </div>
                    </div>

                    <div class="rosaliza-landmarks">
                        <div class="rosaliza-landmarks__head">Khoảng cách từ 5B Trần Quốc Toản</div>
                        <div class="rosaliza-landmarks__grid">
                            <div><span>Hồ Hoàn Kiếm</span><strong>5 phút di chuyển</strong></div>
                            <div><span>Nhà hát Lớn Hà Nội</span><strong>6 phút di chuyển</strong></div>
                            <div><span>Ga Hà Nội</span><strong>4 phút di chuyển</strong></div>
                            <div><span>Sân bay Nội Bài</span><strong>35 - 40 phút</strong></div>
                        </div>
                    </div>

                    <a href="{{ route('rooms.index') }}" class="melia-btn-primary w-100 justify-content-center" style="min-height: 48px;">
                        <span>Kiểm tra phòng trống ngay</span>
                        <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </section>

        {{-- 5. BẢN ĐỒ VỊ TRÍ --}}
        <section class="melia-map-card" aria-labelledby="location-title">
            <div class="melia-map-header">
                <div>
                    <h3 id="location-title" class="melia-map-title">
                        <i class="bi bi-geo-alt-fill" style="color: var(--melia-gold);" aria-hidden="true"></i>
                        <span>5B Trần Quốc Toản, Phường Hàng Bài, Quận Hoàn Kiếm, Hà Nội</span>
                    </h3>
                    <span class="melia-map-sub">
                        Nhận phòng 14:00 - trước 18:00 · Trả phòng trước 12:00 · Lễ tân trực 24/7
                    </span>
                </div>
                <a href="https://maps.google.com/?q=5B+Tran+Quoc+Toan,+Hang+Bai,+Hoan+Kiem,+Ha+Noi" target="_blank" rel="noopener noreferrer" class="melia-btn-secondary" style="padding: 9px 18px; font-size: 12.5px;">
                    <i class="bi bi-compass" aria-hidden="true"></i>
                    <span>Mở Google Maps</span>
                </a>
            </div>
            <div class="melia-map-frame">
                <iframe
                    title="Bản đồ vị trí Rosaliza Hotel tại 5B Trần Quốc Toản, Hoàn Kiếm, Hà Nội"
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps?q=5B+Tr%E1%BA%A7n+Qu%E1%BB%91c+To%E1%BA%A3n,+H%C3%A0ng+B%C3%A0i,+Ho%C3%A0n+Ki%E1%BA%BFm,+H%C3%A0+N%E1%BB%99i&output=embed">
                </iframe>
            </div>
        </section>

    </div>
</div>
@endsection
