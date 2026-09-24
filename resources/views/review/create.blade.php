@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Đánh Giá Trải Nghiệm – ' . htmlspecialchars($roomType->type_name); ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:wght@300;400;500;600;700&display=swap');

:root {
    --gold:       #C9A84C;
    --gold-light: #E8C87A;
    --gold-dark:  #9A7335;
    --ink:        #1A1A2E;
    --cream:      #FAF8F3;
    --muted:      #7A7A8A;
    --border:     #E8E4D8;
    --card-bg:    #FFFFFF;
}

body {
    font-family: 'DM Sans', sans-serif;
    background-color: var(--cream);
}

.review-container {
    max-width: 650px;
    margin: 40px auto;
}

.review-card {
    background-color: var(--card-bg);
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 15px 45px rgba(26, 26, 46, 0.08);
    overflow: hidden;
}

.review-header {
    background: linear-gradient(135deg, var(--ink) 0%, #2D2D44 100%);
    padding: 35px 30px;
    text-align: center;
    border-bottom: 4px solid var(--gold);
}

.review-header h2 {
    font-family: 'Cormorant Garamond', serif;
    color: var(--gold-light);
    font-size: 2.2rem;
    font-weight: 700;
    margin: 0 0 10px 0;
    letter-spacing: 1px;
}

.review-header p {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
    margin: 0;
    font-weight: 300;
    letter-spacing: 0.5px;
}

.review-body {
    padding: 40px 35px;
}

.booking-info {
    background-color: var(--cream);
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 30px;
}

.info-title {
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--muted);
    margin-bottom: 12px;
}

.info-item {
    font-size: 0.95rem;
    color: var(--ink);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-item i {
    color: var(--gold);
}

.info-item:last-child {
    margin-bottom: 0;
}

.star-rating-label {
    font-size: 1rem;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 15px;
    text-align: center;
}

.star-rating {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-bottom: 30px;
}

.star-rating i {
    font-size: 2.8rem;
    cursor: pointer;
    color: #E2E8F0;
    transition: color 0.15s ease, transform 0.15s ease;
}

.star-rating i:hover {
    transform: scale(1.15);
}

.comment-label {
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--ink);
    margin-bottom: 10px;
    display: block;
}

.comment-textarea {
    width: 100%;
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 15px;
    font-size: 0.95rem;
    color: var(--ink);
    background-color: var(--cream);
    resize: vertical;
    min-height: 120px;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.comment-textarea:focus {
    outline: none;
    border-color: var(--gold);
    box-shadow: 0 0 0 3px rgba(201, 168, 76, 0.15);
    background-color: #ffffff;
}

.btn-submit-review {
    width: 100%;
    background: linear-gradient(135deg, var(--gold-dark), var(--gold));
    color: #ffffff;
    border: none;
    border-radius: 12px;
    padding: 14px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
    margin-top: 15px;
}

.btn-submit-review:hover {
    transform: translateY(-1px);
    box-shadow: 0 8px 25px rgba(201, 168, 76, 0.3);
}

.text-gold {
    color: var(--gold) !important;
}
</style>

<div class="container review-container pb-5">
    <div class="review-card">
        <div class="review-header">
            <h2>Đánh Giá Trải Nghiệm</h2>
            <p>Ý kiến đóng góp quý báu của quý khách giúp chúng tôi ngày càng hoàn thiện dịch vụ</p>
        </div>

        <div class="review-body">
            <!-- Thông tin phòng và chuyến đi -->
            <div class="booking-info">
                <div class="info-title">Thông tin dịch vụ</div>
                <div class="info-item">
                    <i class="bi bi-door-open-fill"></i>
                    <strong>Loại phòng:</strong> {{ $roomType->type_name }}
                </div>
                <div class="info-item">
                    <i class="bi bi-calendar3"></i>
                    <strong>Thời gian lưu trú:</strong> 
                    {{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}
                </div>
                <div class="info-item">
                    <i class="bi bi-receipt"></i>
                    <strong>Mã đặt phòng:</strong> #{{ $booking->id }}
                </div>
            </div>

            <!-- Form gửi đánh giá -->
            <form action="{{ route('reviews.store') }}" method="POST">
                @csrf
                <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                <input type="hidden" name="room_type_id" value="{{ $roomType->id }}">

                <!-- Rating -->
                <div class="star-rating-label">Quý khách đánh giá thế nào về chất lượng phòng?</div>
                <div class="star-rating">
                    <input type="hidden" name="rating" id="ratingValue" value="5">
                    <i class="bi bi-star-fill star-item text-gold" data-value="1"></i>
                    <i class="bi bi-star-fill star-item text-gold" data-value="2"></i>
                    <i class="bi bi-star-fill star-item text-gold" data-value="3"></i>
                    <i class="bi bi-star-fill star-item text-gold" data-value="4"></i>
                    <i class="bi bi-star-fill star-item text-gold" data-value="5"></i>
                </div>

                <!-- Bình luận -->
                <div class="mb-4">
                    <label for="comment" class="comment-label">Ý kiến đóng góp khác (nếu có):</label>
                    <textarea name="comment" id="comment" class="comment-textarea" 
                              placeholder="Chia sẻ trải nghiệm chi tiết của quý khách..."></textarea>
                </div>

                <!-- Nút Submit -->
                <button type="submit" class="btn-submit-review">
                    <i class="bi bi-check-circle me-1"></i> Gửi đánh giá của tôi
                </button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-item');
    const input = document.getElementById('ratingValue');

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const val = parseInt(this.dataset.value);
            input.value = val;
            updateStars(val);
        });

        star.addEventListener('mouseenter', function() {
            const val = parseInt(this.dataset.value);
            updateStars(val);
        });
    });

    document.querySelector('.star-rating').addEventListener('mouseleave', function() {
        updateStars(parseInt(input.value));
    });

    function updateStars(val) {
        stars.forEach(s => {
            const sVal = parseInt(s.dataset.value);
            if (sVal <= val) {
                s.classList.remove('bi-star');
                s.classList.add('bi-star-fill');
                s.style.color = '#C9A84C';
            } else {
                s.classList.remove('bi-star-fill');
                s.classList.add('bi-star');
                s.style.color = '#E2E8F0';
            }
        });
    }
});
</script>
@endsection
