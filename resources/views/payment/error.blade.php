@extends('layouts.main')

@section('title', 'Thanh toán thất bại')

@section('content')
<div class="text-center py-5">
    <div style="font-size:80px;margin-bottom:16px">⚠️</div>
    <h1 class="fw-bold text-danger mb-3">Thanh toán thất bại</h1>
    <p class="text-muted fs-5 mb-4">
        Đặt phòng #{{ $booking->id }} chưa được thanh toán thành công.<br>
        Vui lòng thử lại hoặc chọn phương thức thanh toán khác.
    </p>
    <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="{{ route('payment.show', $booking->id) }}" class="btn btn-primary btn-lg">
            <i class="bi bi-arrow-clockwise me-2"></i>Thử lại
        </a>
        <a href="{{ route('booking.mine') }}" class="btn btn-outline-secondary btn-lg">
            <i class="bi bi-list-check me-2"></i>Đặt phòng của tôi
        </a>
    </div>
</div>
@endsection