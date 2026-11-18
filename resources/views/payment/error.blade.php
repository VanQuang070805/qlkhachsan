@extends('layouts.main')
@section('title', 'Thanh toán chưa hoàn tất · Rosaliza Hotel')
@section('content')
<section class="commerce-page payment-state" data-reveal aria-labelledby="payment-error-title" style="padding: clamp(28px, 5vw, 48px) 22px; min-height: auto; margin: 20px auto 72px;">
    <span class="payment-state__icon payment-state__icon--error"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span>
    <p class="editorial-eyebrow">Đặt phòng #{{ $booking->id }}</p>
    <h1 id="payment-error-title" style="font-size: clamp(26px, 4.5vw, 38px); font-weight: 600; letter-spacing: -0.03em;">Thanh toán chưa hoàn tất</h1>
    <p style="font-size: 14.5px; line-height: 1.6;">Giao dịch đặt cọc chưa được xác nhận. Bạn có thể chọn lại phương thức thanh toán hoặc kiểm tra trạng thái trong lịch sử đặt phòng.</p>
    <div class="payment-state__actions">
        <a href="{{ route('payment.form', $booking->id) }}" class="button">Thử thanh toán lại</a>
        <a href="{{ route('booking.mine') }}" class="text-link">Lịch sử đặt phòng</a>
    </div>
</section>
@endsection
