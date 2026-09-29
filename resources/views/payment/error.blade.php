@extends('layouts.main')
@section('title', 'Thanh toán chưa hoàn tất · Royal Hotel')
@section('content')
<section class="commerce-page payment-state" data-reveal aria-labelledby="payment-error-title">
    <span class="payment-state__icon payment-state__icon--error"><i class="bi bi-exclamation" aria-hidden="true"></i></span>
    <p class="editorial-eyebrow">Đặt phòng #{{ $booking->id }}</p>
    <h1 id="payment-error-title">Payment incomplete</h1>
    <p>Giao dịch chưa được xác nhận. Bạn có thể thử lại hoặc trở về danh sách kỳ nghỉ của mình.</p>
    <div class="payment-state__actions"><a href="{{ route('payment.show',$booking->id) }}" class="button">Thử thanh toán lại</a><a href="{{ route('booking.mine') }}" class="text-link">Kỳ nghỉ của tôi <span aria-hidden="true">→</span></a></div>
</section>
@endsection
