@extends('client.layouts.app')
@section('title', 'Phòng nghỉ · Royal Hotel | Lưu trú tinh tế')

@section('content')
{{-- =========================================================================
     ROOMS HERO SECTION (SUPASTE ELECTRIC BLUE + DUAL-FONT HEADLINE)
     ========================================================================= --}}
<section class="page-hero supaste-rooms-hero" aria-labelledby="rooms-page-title">
    <div class="supaste-rooms-hero__inner">
        <h1 id="rooms-page-title" class="supaste-hero__headline">
            <span class="supaste-hero__headline-sans">A room for</span>
            <span class="supaste-hero__headline-serif">every journey.</span>
        </h1>
        <p class="supaste-hero__subtitle">
            Khám phá bộ sưu tập phòng nghỉ sang trọng, từ hạng phòng tiêu chuẩn tinh tế đến các căn Presidential Suite đỉnh cao tại Royal Hotel.
        </p>
    </div>
</section>

{{-- =========================================================================
     ROOMS CATALOGUE SECTION (SUPASTE BENTO CARDS GRID)
     ========================================================================= --}}
<section class="supaste-section" style="padding-top: 60px; padding-bottom: 90px; max-width: 1280px; margin: 0 auto;" aria-labelledby="catalogue-title">
    <div class="supaste-section__header" style="margin-bottom: 40px;">
        <p class="supaste-section__eyebrow">BỘ SƯU TẬP PHÒNG NGHỈ</p>
        <h2 id="catalogue-title" class="supaste-section__title">Không gian lưu trú thanh lịch</h2>
        <p class="supaste-section__desc">
            Mỗi phòng nghỉ tại Royal Hotel được thiết kế tỉ mỉ, kết hợp sự tiện nghi tối tân cùng cảm giác an yên tuyệt đối.
        </p>
    </div>

    @if($rooms->isNotEmpty())
        <div class="supaste-rooms-grid" id="rooms-cards-grid">
            @foreach($rooms as $room)
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
                <article class="supaste-room-card" data-room-category="{{ $category }}" data-reveal>
                    <div class="macos-card-bar" style="padding: 10px 16px; background: #ffffff !important; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; border-radius: 22px 22px 0 0;">
                        <div style="display: flex; gap: 6px;">
                            <span class="ctrl-dot ctrl-red"></span>
                            <span class="ctrl-dot ctrl-yellow"></span>
                            <span class="ctrl-dot ctrl-green"></span>
                        </div>
                        <span style="font-size: 11px; font-weight: 600; color: #475569; letter-spacing: -0.01em;">{{ $room->type_name }}</span>
                        <div style="width: 32px;"></div>
                    </div>
                    <a class="supaste-room-card__img" href="{{ route('rooms.detail', $room->id) }}">
                        <img src="{{ $room->image ?: config('room_images.' . $room->id . '.0', asset('images/rooms/default.jpg')) }}"
                             alt="{{ $room->type_name }} tại Royal Hotel"
                             loading="lazy"
                             onerror="this.onerror=null;this.src='{{ asset('images/rooms/default.jpg') }}';">
                        <span class="supaste-room-card__badge">
                            {{ $room->available_count }} phòng trống
                        </span>
                    </a>
                    <div class="supaste-room-card__info" style="padding: 18px 20px 22px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 11.5px; color: #64748b; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase;">
                            <span>{{ strtoupper($category) }}</span>
                            <span>Tối đa {{ $room->max_guests }} khách</span>
                        </div>

                        <h3 style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 8px 0; line-height: 1.3;">
                            <a href="{{ route('rooms.detail', $room->id) }}" style="color: inherit; text-decoration: none;">
                                {{ $room->type_name }}
                            </a>
                        </h3>

                        <p style="font-size: 13.5px; color: #64748b; line-height: 1.6; margin: 0 0 16px 0; min-height: 44px;">
                            {{ \Illuminate\Support\Str::limit($room->description ?: 'Không gian lưu trú cao cấp được trang bị tiện nghi đầy đủ cho kỳ nghỉ hoàn hảo.', 110) }}
                        </p>

                        <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 20px;">
                            @forelse($room->amenities->take(3) as $amenity)
                                <span style="background: #f1f5f9; color: #475569; font-size: 11.5px; padding: 4px 10px; border-radius: 999px; font-weight: 500;">
                                    {{ $amenity->amenity_name }}
                                </span>
                            @empty
                                <span style="background: #f1f5f9; color: #475569; font-size: 11.5px; padding: 4px 10px; border-radius: 999px; font-weight: 500;">
                                    Wi-Fi tốc độ cao
                                </span>
                                <span style="background: #f1f5f9; color: #475569; font-size: 11.5px; padding: 4px 10px; border-radius: 999px; font-weight: 500;">
                                    Bữa sáng buffet
                                </span>
                            @endforelse
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: flex-end; padding-top: 14px; border-top: 1px solid #f1f5f9; margin-top: auto;">
                            <div>
                                <span style="font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; display: block;">Giá mỗi đêm</span>
                                <strong style="font-size: 19px; color: #0066ff; font-weight: 700;">
                                    {{ number_format((float) $room->price, 0, ',', '.') }} đ
                                </strong>
                            </div>
                            <a href="{{ route('rooms.detail', $room->id) }}" class="supaste-card-btn" style="width: auto; padding: 9px 18px;">
                                Chi tiết →
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div style="text-align: center; padding: 60px 20px; background: #f8fafc; border-radius: 24px; border: 1px dashed #cbd5e1;">
            <p style="color: #64748b; font-size: 15px; margin-bottom: 16px;">Hiện chưa có hạng phòng trong hệ thống.</p>
            <a class="button button--small" href="{{ route('contact') }}">Liên hệ tư vấn</a>
        </div>
    @endif
</section>

{{-- =========================================================================
     STANDARDS & SERVICES SECTION (MACOS FROSTED GLASS BENTO)
     ========================================================================= --}}
<section class="supaste-section" style="padding-top: 20px; padding-bottom: 80px; max-width: 1280px; margin: 0 auto;" aria-labelledby="standards-title">
    <div class="supaste-section__header">
        <p class="supaste-section__eyebrow">DỊCH VỤ &amp; TIÊU CHUẨN</p>
        <h2 id="standards-title" class="supaste-section__title">Đặc quyền nghỉ dưỡng chuẩn mực</h2>
        <p class="supaste-section__desc">
            Mỗi khoảnh khắc tại Royal Hotel đều được kiến tạo từ sự chu đáo, tận tâm và tiêu chuẩn dịch vụ khách sạn 5 sao quốc tế.
        </p>
    </div>

    <div class="supaste-features-grid">
        <div class="supaste-feature-card" data-reveal>
            <div style="display: flex; gap: 5px; margin-bottom: 16px;">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <div class="supaste-feature-card__icon" style="color: #0071e3;">
                <i class="bi bi-shield-check"></i>
            </div>
            <h3>Dịch vụ quản gia 24/7</h3>
            <p>Đội ngũ Concierge và quản gia chuyên nghiệp luôn sẵn sàng hỗ trợ mọi nhu cầu lưu trú, đưa đón sân bay và ẩm thực theo yêu cầu.</p>
        </div>

        <div class="supaste-feature-card" data-reveal>
            <div style="display: flex; gap: 5px; margin-bottom: 16px;">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <div class="supaste-feature-card__icon" style="color: #10b981;">
                <i class="bi bi-cup-hot"></i>
            </div>
            <h3>Ẩm thực thượng hạng</h3>
            <p>Bữa sáng buffet phong cách quốc tế cùng thực đơn món Á - Âu tinh tế được chuẩn bị bởi các đầu bếp hàng đầu.</p>
        </div>

        <div class="supaste-feature-card" data-reveal>
            <div style="display: flex; gap: 5px; margin-bottom: 16px;">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <div class="supaste-feature-card__icon" style="color: #8b5cf6;">
                <i class="bi bi-soundwave"></i>
            </div>
            <h3>Không gian tĩnh lặng</h3>
            <p>Hệ thống cửa cách âm chuẩn studio và nệm memory foam cao cấp đảm bảo giấc ngủ sâu và trọn vẹn nhất.</p>
        </div>
    </div>
</section>

{{-- =========================================================================
     FAQ SECTION (MACOS ACCORDION)
     ========================================================================= --}}
<section class="supaste-section" style="padding-top: 20px; padding-bottom: 100px; max-width: 1280px; margin: 0 auto;" aria-labelledby="faq-title">
    <div class="supaste-section__header">
        <p class="supaste-section__eyebrow">HỎI ĐÁP LƯU TRÚ</p>
        <h2 id="faq-title" class="supaste-section__title">Câu hỏi thường gặp</h2>
        <p class="supaste-section__desc">
            Những thông tin cần biết để kỳ nghỉ của bạn diễn ra suôn sẻ và tiện lợi nhất.
        </p>
    </div>

    <div class="supaste-faq-container" style="max-width: 820px; margin: 0 auto;">
        <div class="supaste-faq-item">
            <details>
                <summary>Thời gian nhận phòng và trả phòng tiêu chuẩn là khi nào?</summary>
                <p>Giờ nhận phòng tiêu chuẩn là từ 14:00 và giờ trả phòng trước 12:00 trưa hôm sau. Quý khách có thể yêu cầu nhận phòng sớm hoặc trả phòng muộn tùy thuộc vào tình trạng phòng trống.</p>
            </details>
        </div>
        <div class="supaste-faq-item">
            <details>
                <summary>Chính sách hủy phòng và hoàn tiền như thế nào?</summary>
                <p>Royal Hotel hỗ trợ hủy phòng hoàn toàn miễn phí nếu thực hiện trước 48 giờ so với thời điểm nhận phòng. Tiền đặt cọc sẽ được hoàn trả tự động theo phương thức thanh toán ban đầu.</p>
            </details>
        </div>
        <div class="supaste-faq-item">
            <details>
                <summary>Khách sạn có dịch vụ đón tiễn sân bay không?</summary>
                <p>Có. Chúng tôi cung cấp dịch vụ đưa đón sân bay 2 chiều bằng xe Limousine cao cấp. Đối với hạng phòng Presidential Suite, dịch vụ đón tiễn sân bay được phục vụ hoàn toàn miễn phí.</p>
            </details>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.gsap && window.ScrollTrigger) {
        gsap.from('.supaste-room-card', {
            scrollTrigger: {
                trigger: '#rooms-cards-grid',
                start: 'top 85%',
                toggleActions: 'play none none none'
            },
            y: 35,
            opacity: 0,
            duration: 0.65,
            stagger: 0.1,
            ease: 'power3.out',
            clearProps: 'transform'
        });
    }
});
</script>
@endpush
