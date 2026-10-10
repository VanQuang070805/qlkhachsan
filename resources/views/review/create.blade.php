@extends('client.layouts.app')

@section('title', 'Đánh giá trải nghiệm · ' . ($roomType->type_name ?? 'Phòng') . ' · Rosaliza Hotel')

@push('styles')
<style>
/* ==========================================================================
   Rosaliza Hotel — Customer Room Review Experience (Apple macOS Quiet Luxury)
   ========================================================================== */
.review-wrapper {
    max-width: 760px;
    margin: 0 auto;
    padding: calc(64px + env(safe-area-inset-top, 0px)) 20px 80px;
}

.review-breadcrumb {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #64748b;
    text-decoration: none;
    margin-bottom: 24px;
    padding: 6px 14px;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(0, 0, 0, 0.06);
    transition: all 0.2s ease;
}

.review-breadcrumb:hover {
    color: #0071e3;
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 113, 227, 0.08);
    transform: translateX(-2px);
}

/* Hero Header */
.review-hero {
    text-align: center;
    margin-bottom: 32px;
}

.review-badge {
    display: inline-block;
    padding: 5px 14px;
    border-radius: 999px;
    background: rgba(0, 113, 227, 0.08);
    color: #0071e3;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 12px;
    border: 1px solid rgba(0, 113, 227, 0.16);
}

.review-hero h1 {
    font-size: clamp(28px, 4.5vw, 40px);
    font-weight: 700;
    color: #0f172a;
    letter-spacing: -0.025em;
    line-height: 1.2;
    margin: 0 0 10px;
}

.review-hero h1 em {
    font-family: 'Instrument Serif', Georgia, serif;
    font-style: italic;
    font-weight: 400;
    color: #0071e3;
}

.review-hero p {
    font-size: 14.5px;
    color: #64748b;
    max-width: 520px;
    margin: 0 auto;
    line-height: 1.6;
}

/* macOS Window Card */
.review-window-card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    box-shadow: 0 20px 60px rgba(15, 23, 42, 0.06), 0 2px 8px rgba(0, 0, 0, 0.03);
    overflow: hidden;
}

.review-window-topbar {
    background: linear-gradient(180deg, #10121a 0%, #171b26 100%);
    padding: 14px 20px;
    display: flex;
    align-items: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.review-window-controls {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-right: 16px;
}

.review-window-controls .ctrl-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
}

.review-window-controls .ctrl-red { background: #ff5f57; }
.review-window-controls .ctrl-yellow { background: #febc2e; }
.review-window-controls .ctrl-green { background: #28c840; }

.review-window-title {
    color: #e2e8f0;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: -0.01em;
    margin: 0;
    opacity: 0.9;
}

.review-body {
    padding: 32px 30px;
}

/* Booking & Room Preview Banner */
.review-room-banner {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    margin-bottom: 28px;
}

.review-room-thumb {
    width: 72px;
    height: 72px;
    border-radius: 12px;
    object-fit: cover;
    flex-shrink: 0;
    border: 1px solid rgba(0, 0, 0, 0.06);
}

.review-room-meta {
    flex: 1 1 auto;
    min-width: 0;
}

.review-room-title {
    font-size: 16px;
    font-weight: 700;
    color: #0f172a;
    margin: 0 0 4px;
    letter-spacing: -0.01em;
}

.review-stay-dates {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: #64748b;
}

.review-stay-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #ffffff;
    padding: 2px 8px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
}

/* Interactive Star Rating Section */
.rating-section {
    text-align: center;
    padding: 24px 16px;
    margin-bottom: 24px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border-radius: 18px;
    border: 1px solid #edf2f7;
}

.rating-section-label {
    font-size: 14px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 12px;
    display: block;
}

.stars-interactive {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 12px;
}

.star-btn {
    background: transparent;
    border: none;
    padding: 4px;
    cursor: pointer;
    color: #cbd5e1;
    font-size: 38px;
    line-height: 1;
    transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), color 0.18s ease;
    border-radius: 8px;
    outline: none;
}

.star-btn:hover {
    transform: scale(1.18);
}

.star-btn.is-active {
    color: #f59e0b;
    filter: drop-shadow(0 2px 8px rgba(245, 158, 11, 0.35));
}

.rating-descriptor {
    display: inline-block;
    padding: 5px 16px;
    background: #ffffff;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
    color: #d97706;
    border: 1px solid #fef3c7;
    box-shadow: 0 2px 8px rgba(245, 158, 11, 0.1);
    transition: all 0.2s ease;
}

/* Quick Compliment Tag Chips */
.quick-tags-section {
    margin-bottom: 22px;
}

.quick-tags-label {
    font-size: 13px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 10px;
    display: block;
}

.quick-tags-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.quick-tag-chip {
    padding: 6px 14px;
    border-radius: 999px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #334155;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.16s ease;
    user-select: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.quick-tag-chip:hover {
    background: #e2e8f0;
    border-color: #cbd5e1;
    color: #0f172a;
}

.quick-tag-chip.is-selected {
    background: rgba(0, 113, 227, 0.1);
    border-color: #0071e3;
    color: #0071e3;
    font-weight: 600;
}

/* Comment Form Control */
.comment-group {
    margin-bottom: 22px;
}

.comment-group label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13.5px;
    font-weight: 600;
    color: #0f172a;
    margin-bottom: 8px;
}

.comment-counter {
    font-size: 11.5px;
    font-weight: 500;
    color: #94a3b8;
}

.comment-box {
    width: 100%;
    min-height: 130px;
    padding: 14px 16px;
    border-radius: 14px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    font-size: 14px;
    line-height: 1.6;
    color: #0f172a;
    font-family: inherit;
    resize:vertical;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.comment-box:focus {
    outline: none;
    background: #ffffff;
    border-color: #0071e3;
    box-shadow: 0 0 0 3px rgba(0, 113, 227, 0.15);
}

.comment-box::placeholder {
    color: #94a3b8;
}

/* Security / Privacy Trust Note */
.review-trust-note {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
    color: #64748b;
    padding: 10px 14px;
    background: #f8fafc;
    border-radius: 10px;
    margin-bottom: 24px;
    border: 1px solid #e2e8f0;
}

.review-trust-note i {
    color: #10b981;
    font-size: 14px;
}

/* Action Button */
.review-submit-btn {
    width: 100%;
    height: 48px;
    border-radius: 999px;
    background: #0071e3;
    color: #ffffff;
    font-size: 14.5px;
    font-weight: 700;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 16px rgba(0, 113, 227, 0.28);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s ease;
    text-decoration: none;
}

.review-submit-btn:hover {
    background: #0056b3;
    box-shadow: 0 6px 20px rgba(0, 113, 227, 0.38);
    transform: translateY(-1px);
}

.review-submit-btn:active {
    transform: scale(0.98);
}

/* Mobile & Tablet Optimizations */
@media (max-width: 640px) {
    .review-wrapper {
        padding: calc(48px + env(safe-area-inset-top, 0px)) 14px 100px;
    }

    .review-body {
        padding: 20px 16px;
    }

    .star-btn {
        font-size: 32px;
        padding: 2px;
    }

    .review-room-thumb {
        width: 58px;
        height: 58px;
    }

    .review-room-title {
        font-size: 14.5px;
    }
}
</style>
@endpush

@section('content')
<div class="review-wrapper">
    {{-- Back to Room Detail --}}
    <a href="{{ route('rooms.detail', $roomType->id) }}" class="review-breadcrumb">
        <i class="bi bi-chevron-left" aria-hidden="true"></i>
        <span>Quay lại {{ $roomType->type_name }}</span>
    </a>

    {{-- Hero Section --}}
    <div class="review-hero">
        <span class="review-badge">Trải nghiệm lưu trú</span>
        <h1>Cảm nhận của quý khách <em>tại Rosaliza Hotel</em></h1>
        <p>Mỗi chia sẻ chân thành của quý khách là nguồn cảm hứng quý báu giúp chúng tôi nâng tầm chất lượng kỳ nghỉ ngày một hoàn hảo hơn.</p>
    </div>

    {{-- macOS Window Card --}}
    <article class="review-window-card">
        {{-- macOS Topbar --}}
        <header class="review-window-topbar">
            <div class="review-window-controls" aria-hidden="true">
                <span class="ctrl-dot ctrl-red"></span>
                <span class="ctrl-dot ctrl-yellow"></span>
                <span class="ctrl-dot ctrl-green"></span>
            </div>
            <p class="review-window-title">Rosaliza Hotel — Phiếu đánh giá trải nghiệm #{{ $booking->id }}</p>
        </header>

        <div class="review-body">
            {{-- Room Summary Banner --}}
            <div class="review-room-banner">
                @php
                    $roomImg = !empty($roomType->image) ? asset($roomType->image) : asset('images/rooms/deluxe-room.jpg');
                @endphp
                <img src="{{ $roomImg }}" alt="{{ $roomType->type_name }}" class="review-room-thumb">
                <div class="review-room-meta">
                    <h3 class="review-room-title">{{ $roomType->type_name }}</h3>
                    <div class="review-stay-dates">
                        <span class="review-stay-chip">
                            <i class="bi bi-calendar-check" aria-hidden="true"></i>
                            {{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}
                        </span>
                        <span class="review-stay-chip">
                            <i class="bi bi-hash" aria-hidden="true"></i>
                            Đơn đặt #{{ $booking->id }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Form Submit --}}
            <form action="{{ route('reviews.store') }}" method="POST" id="reviewForm">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                <input type="hidden" name="room_type_id" value="{{ $roomType->id }}">
                <input type="hidden" name="rating" id="ratingInput" value="{{ old('rating', 5) }}">

                {{-- Star Rating Section --}}
                <div class="rating-section">
                    <label class="rating-section-label" id="rating-title">Mức độ hài lòng của quý khách</label>
                    <div class="stars-interactive" role="radiogroup" aria-labelledby="rating-title">
                        @for ($i = 1; $i <= 5; $i++)
                            <button type="button"
                                    class="star-btn {{ (int)old('rating', 5) >= $i ? 'is-active' : '' }}"
                                    data-value="{{ $i }}"
                                    role="radio"
                                    aria-checked="{{ (int)old('rating', 5) === $i ? 'true' : 'false' }}"
                                    aria-label="{{ $i }} sao"
                                    title="{{ $i }} sao">
                                ★
                            </button>
                        @endfor
                    </div>
                    <div>
                        <span class="rating-descriptor" id="ratingDescriptor">5 sao · Xuất sắc vượt mong đợi</span>
                    </div>
                </div>

                {{-- Quick Compliment Chips --}}
                <div class="quick-tags-section">
                    <span class="quick-tags-label">Gợi ý cảm nhận nhanh (nhấn để thêm vào chia sẻ):</span>
                    <div class="quick-tags-grid">
                        <span class="quick-tag-chip" data-text="Phòng ốc sạch sẽ, bài trí trang nhã và tiện nghi đầy đủ.">✨ Phòng sạch &amp; tiện nghi</span>
                        <span class="quick-tag-chip" data-text="Giường nệm rất êm ái, cách âm tốt mang lại giấc ngủ thư thái.">🛏️ Giường êm &amp; cách âm tốt</span>
                        <span class="quick-tag-chip" data-text="Đội ngũ nhân viên tiếp đón nồng hậu, lịch thiệp và hỗ trợ tận tình.">🛎️ Nhân viên lịch thiệp, chu đáo</span>
                        <span class="quick-tag-chip" data-text="Không gian yên bình, riêng tư, rất thích hợp để nghỉ ngơi.">🌿 Yên tĩnh, riêng tư</span>
                        <span class="quick-tag-chip" data-text="Tầm nhìn ban công thoáng mát và cảnh quan tuyệt đẹp.">🌅 Tầm nhìn ban công đẹp</span>
                        <span class="quick-tag-chip" data-text="Bữa sáng chuẩn vị, phong phú và rất ngon miệng.">☕ Ẩm thực chu đáo</span>
                    </div>
                </div>

                {{-- Comment Box --}}
                <div class="comment-group">
                    <label for="comment">
                        <span>Chia sẻ chi tiết cảm nhận của bạn</span>
                        <span class="comment-counter" id="charCounter">0 / 1000</span>
                    </label>
                    <textarea name="comment"
                              id="comment"
                              class="comment-box"
                              style="resize:vertical;"
                              maxlength="1000"
                              placeholder="Điều gì khiến kỳ nghỉ của quý khách đáng nhớ nhất? Những điểm hài lòng hoặc đóng góp để khách sạn phục vụ tốt hơn...">{{ old('comment') }}</textarea>
                    @error('comment')
                        <p style="color: #ef4444; font-size: 12px; margin-top: 6px;">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Trust Note --}}
                <div class="review-trust-note">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    <span>Đánh giá được bảo chứng bởi hệ thống xác thực kỳ nghỉ thực tế tại Rosaliza Hotel.</span>
                </div>

                {{-- Submit CTA --}}
                <button type="submit" class="review-submit-btn">
                    <span>Gửi đánh giá trải nghiệm</span>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </article>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ratingInput = document.getElementById('ratingInput');
    const starBtns = document.querySelectorAll('.star-btn');
    const descriptor = document.getElementById('ratingDescriptor');
    const textarea = document.getElementById('comment');
    const counter = document.getElementById('charCounter');
    const chips = document.querySelectorAll('.quick-tag-chip');

    const descriptors = {
        1: '1 sao · Cần cải thiện dịch vụ',
        2: '2 sao · Tạm ổn, chưa thực sự trọn vẹn',
        3: '3 sao · Đạt tiêu chuẩn kỳ nghỉ',
        4: '4 sao · Rất tốt & hài lòng',
        5: '5 sao · Xuất sắc vượt mong đợi'
    };

    function updateStars(val) {
        ratingInput.value = val;
        starBtns.forEach(btn => {
            const starVal = parseInt(btn.dataset.value, 10);
            if (starVal <= val) {
                btn.classList.add('is-active');
            } else {
                btn.classList.remove('is-active');
            }
            btn.setAttribute('aria-checked', starVal === val ? 'true' : 'false');
        });
        if (descriptor && descriptors[val]) {
            descriptor.textContent = descriptors[val];
        }
    }

    starBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const val = parseInt(btn.dataset.value, 10);
            updateStars(val);
        });
    });

    // Character counter
    if (textarea && counter) {
        const updateCount = () => {
            counter.textContent = `${textarea.value.length} / 1000`;
        };
        textarea.addEventListener('input', updateCount);
        updateCount();
    }

    // Quick tag chips tap to add
    chips.forEach(chip => {
        chip.addEventListener('click', () => {
            chip.classList.toggle('is-selected');
            const text = chip.dataset.text;
            if (!textarea) return;

            let current = textarea.value.trim();
            if (chip.classList.contains('is-selected')) {
                if (current.length > 0) {
                    textarea.value = current + ' ' + text;
                } else {
                    textarea.value = text;
                }
            } else {
                textarea.value = current.replace(text, '').trim();
            }
            if (counter) counter.textContent = `${textarea.value.length} / 1000`;
            textarea.focus();
        });
    });

    // Initialize with current value
    const initialVal = parseInt(ratingInput.value || 5, 10);
    updateStars(initialVal);
});
</script>
@endpush
