@extends('client.layouts.app')

@section('title', 'Về Royal & Liên hệ · Royal Hotel | Lưu trú tinh tế')

@section('content')
<div class="contact-page contact-page--full">
    <section class="page-hero supaste-rooms-hero contact-hero-full" aria-labelledby="contact-title">
        <div class="supaste-rooms-hero__inner">
            <h1 id="contact-title" class="supaste-hero__headline">
                <span class="supaste-hero__headline-sans">A thoughtful stay,</span>
                <span class="supaste-hero__headline-serif">close to everything.</span>
            </h1>
            <p class="supaste-hero__subtitle">
                Một nơi dừng chân thanh lịch, riêng tư và đủ gần để bạn chạm tới mọi nhịp sống Hà Nội.
            </p>
            <div class="contact-hero__actions" style="margin-top: 28px; display: flex; align-items: center; justify-content: center; gap: 14px;">
                <a class="button button--primary" href="#contact-details" data-gsap-scroll-to style="border-radius: 999px; background: #000000; color: #ffffff; padding: 12px 28px; font-weight: 600; text-decoration: none; box-shadow: 0 4px 14px rgba(0,0,0,0.25);">Liên hệ với Royal</a>
                <a class="contact-hero__scroll" href="#royal-story" data-gsap-scroll-to style="border-radius: 999px; background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); color: #ffffff; padding: 12px 24px; font-weight: 600; text-decoration: none; border: 1px solid rgba(255,255,255,0.4);">Câu chuyện của chúng tôi <span aria-hidden="true">↓</span></a>
            </div>
        </div>
    </section>

    <div class="contact-body-container" style="max-width: 1240px; margin: 0 auto; padding: 0 24px;">

    <section class="contact-story" id="royal-story" aria-labelledby="royal-story-title">
        <div class="contact-story__copy" data-reveal>
            <p class="editorial-eyebrow">Our story</p>
            <h2 id="royal-story-title">Quiet by design</h2>
            <p>Royal Hotel được tạo nên cho những hành trình cần sự đơn giản và chỉn chu. Từ cách chọn phòng rõ ràng đến lúc bạn nhận phòng, mỗi chi tiết đều hướng đến cảm giác thư thái.</p>
            <dl class="contact-story__facts" aria-label="Thông tin Royal Hotel">
                <div><dt>05</dt><dd>Hạng phòng</dd></div>
                <div><dt>24/7</dt><dd>Luôn sẵn sàng</dd></div>
                <div><dt>12</dt><dd>Chùa Bộc</dd></div>
            </dl>
        </div>
        <div class="contact-gallery" data-resort-gallery data-reveal aria-label="Album không gian nghỉ dưỡng cao cấp">
            <div class="macos-card-bar" style="border-radius: 24px 24px 0 0; background: rgba(248, 250, 252, 0.95); padding: 16px 24px;">
                <div class="window-controls">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <span class="macos-card-title">Không gian kiến trúc Royal Hotel — Photo Gallery</span>
                <div style="width: 52px;" aria-hidden="true"></div>
            </div>
            <div class="contact-gallery__viewport" data-gallery-viewport role="region" aria-label="Kéo ngang hoặc dùng phím mũi tên để xem album" tabindex="0">
                <div class="contact-gallery__track" data-gallery-track>
                    <figure class="contact-gallery__slide"><img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1920&q=88" alt="Khu nghỉ dưỡng với hồ bơi và kiến trúc gỗ" loading="eager"></figure>
                    <figure class="contact-gallery__slide"><img src="https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=1920&q=88" alt="Khách sạn nghỉ dưỡng sang trọng bên hàng cọ" loading="lazy"></figure>
                    <figure class="contact-gallery__slide"><img src="https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=1920&q=88" alt="Hồ bơi vô cực nhìn ra không gian biển" loading="lazy"></figure>
                    <figure class="contact-gallery__slide"><img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=1920&q=88" alt="Phòng nghỉ cao cấp với thiết kế sạch và hiện đại" loading="lazy"></figure>
                    <figure class="contact-gallery__slide"><img src="https://images.unsplash.com/photo-1571896349842-33c89424de2d?auto=format&fit=crop&w=1920&q=88" alt="Không gian resort thanh lịch bên hồ bơi" loading="lazy"></figure>
                </div>
            </div>
            <div class="contact-gallery__controls" style="padding: 16px 24px 20px 24px;">
                <div class="contact-gallery__dots" data-gallery-dots aria-label="Chọn ảnh trong album"></div>
                <div class="contact-gallery__arrows">
                    <button type="button" data-gallery-toggle aria-label="Tạm dừng tự động chuyển ảnh" aria-pressed="false"><i class="bi bi-pause-fill" aria-hidden="true"></i></button>
                    <button type="button" data-gallery-prev aria-label="Xem ảnh trước"><i class="bi bi-arrow-left" aria-hidden="true"></i></button>
                    <button type="button" data-gallery-next aria-label="Xem ảnh tiếp theo"><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
                </div>
            </div>
        </div>
    </section>

    <section class="contact-principles" aria-labelledby="principles-title">
        <div class="editorial-heading" data-reveal>
            <p class="editorial-eyebrow">Our approach</p>
            <h2 id="principles-title">Simply considered</h2>
            <p>Ba điều chúng tôi gìn giữ trong mỗi kỳ lưu trú.</p>
        </div>
        <div class="about-value-grid">
            <article class="about-value-card" data-reveal>
                <span class="about-value-card__index">01</span>
                <div class="about-value-card__icon">
                    <i class="bi bi-wind" aria-hidden="true"></i>
                </div>
                <h3>Room to breathe</h3>
                <p>Không gian yên tĩnh để bạn thật sự nghỉ ngơi sau một ngày dài.</p>
            </article>

            <article class="about-value-card" data-reveal>
                <span class="about-value-card__index">02</span>
                <div class="about-value-card__icon">
                    <i class="bi bi-gem" aria-hidden="true"></i>
                </div>
                <h3>Care in detail</h3>
                <p>Thông tin rõ ràng và trải nghiệm liền mạch từ lúc chọn đến khi nhận phòng.</p>
            </article>

            <article class="about-value-card" data-reveal>
                <span class="about-value-card__index">03</span>
                <div class="about-value-card__icon">
                    <i class="bi bi-headset" aria-hidden="true"></i>
                </div>
                <h3>Here when needed</h3>
                <p>Đội ngũ luôn sẵn sàng lắng nghe và giúp bạn chuẩn bị kỳ nghỉ.</p>
            </article>
        </div>
    </section>

    <section class="about-journey contact-journey" aria-labelledby="journey-title">
        <div class="about-journey__intro" data-reveal>
            <p class="editorial-eyebrow">Your journey</p>
            <h2 id="journey-title">From choice to rest</h2>
            <p>Mọi bước được giữ gọn để bạn dành nhiều thời gian hơn cho chuyến đi.</p>
            <a class="button" href="{{ route('rooms.index') }}">Khám phá phòng <span aria-hidden="true">→</span></a>
        </div>
        <ol class="about-journey__steps">
            <li data-reveal><span>01</span><div><h3>Choose</h3><p>Xem rõ sức chứa, tiện nghi, giá và số phòng còn trống.</p></div><i class="bi bi-calendar2-check" aria-hidden="true"></i></li>
            <li data-reveal><span>02</span><div><h3>Confirm</h3><p>Xác nhận thông tin và chọn phương thức thanh toán phù hợp.</p></div><i class="bi bi-credit-card" aria-hidden="true"></i></li>
            <li data-reveal><span>03</span><div><h3>Unwind</h3><p>Nhận hỗ trợ khi cần và nghỉ ngơi theo nhịp riêng của bạn.</p></div><i class="bi bi-cup-hot" aria-hidden="true"></i></li>
        </ol>
    </section>

    <section class="contact-content" id="contact-details" aria-labelledby="contact-details-title">
        <div class="editorial-heading contact-content__heading" data-reveal>
            <p class="editorial-eyebrow">Contact</p>
            <h2 id="contact-details-title">Always here</h2>
            <p>Gọi, gửi email hoặc ghé thăm chúng tôi tại Chùa Bộc.</p>
        </div>
        <div class="contact-methods">
            <a class="contact-method" href="tel:0123456789" aria-label="Gọi Royal Hotel theo số 0123 456 789" data-reveal><span class="contact-method__icon"><i class="bi bi-telephone" aria-hidden="true"></i></span><span class="contact-method__body"><span class="contact-method__label">Điện thoại</span><strong>0123 456 789</strong><span class="contact-method__hint">Hỗ trợ nhanh qua cuộc gọi</span></span><span class="contact-method__arrow" aria-hidden="true">↗</span></a>
            <a class="contact-method" href="mailto:royalhotel@gmail.com" aria-label="Gửi email đến royalhotel@gmail.com" data-reveal><span class="contact-method__icon"><i class="bi bi-envelope" aria-hidden="true"></i></span><span class="contact-method__body"><span class="contact-method__label">Email</span><strong>royalhotel@gmail.com</strong><span class="contact-method__hint">Phản hồi yêu cầu chi tiết</span></span><span class="contact-method__arrow" aria-hidden="true">↗</span></a>
        </div>
    </section>

    <section class="contact-location" aria-labelledby="contact-location-title">
        <div class="contact-location__copy" data-reveal>
            <p class="editorial-eyebrow">Location</p>
            <h2 id="contact-location-title">Find us</h2>
            <address>12 Chùa Bộc,<br>Đống Đa, Hà Nội</address>
            <p class="contact-location__note"><i class="bi bi-clock" aria-hidden="true"></i> Lễ tân hỗ trợ 24 giờ mỗi ngày · Nhận phòng 12:00 - 17:00</p>
            <a class="button" href="https://www.google.com/maps/search/?api=1&query=12+Ch%C3%B9a+B%E1%BB%99c%2C+%C4%90%E1%BB%91ng+%C4%90a%2C+H%C3%A0+N%E1%BB%99i" target="_blank" rel="noopener noreferrer">Mở chỉ đường <span aria-hidden="true">↗</span></a>
        </div>
        <div class="contact-map" data-reveal style="overflow: hidden; border-radius: 28px; border: 1px solid rgba(0, 0, 0, 0.08); background: #ffffff; box-shadow: 0 16px 40px rgba(0, 0, 0, 0.06); padding-bottom: 20px;">
            <div class="macos-card-bar" style="background: rgba(248, 250, 252, 0.95); padding: 14px 20px; border-bottom: 1px solid rgba(0, 0, 0, 0.06); display: flex; align-items: center; justify-content: space-between;">
                <div class="window-controls">
                    <span class="ctrl-dot ctrl-red"></span>
                    <span class="ctrl-dot ctrl-yellow"></span>
                    <span class="ctrl-dot ctrl-green"></span>
                </div>
                <span class="macos-card-title">Bản đồ Royal Hotel — 12 Chùa Bộc, Hà Nội</span>
                <div style="width: 52px;" aria-hidden="true"></div>
            </div>
            <div style="margin: 14px 20px 0 20px; border-radius: 18px; overflow: hidden; height: 380px; box-shadow: 0 4px 16px rgba(0,0,0,0.06); border: 1px solid rgba(0,0,0,0.04);">
                <iframe title="Bản đồ Royal Hotel tại 12 Chùa Bộc, Hà Nội" src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.6203769615904!2d105.82535447503089!3d21.00784918063632!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ac806cfc0845%3A0x3848505bd3b9490f!2zMTIgUC4gQ2jDuWEgQuG7mWMsIEtpbSBMacOqbiwgSMOgIE7hu5lpIDEwMDAwMCwgVmnhu4d0IE5hbQ!5e0!3m2!1svi!2s!4v1777979688214!5m2!1svi!2s" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen style="width: 100%; height: 100%; border: 0; display: block;"></iframe>
            </div>
        </div>
    </section>
    </div>
</div>
@endsection
