@extends('layouts.main')

@section('title', 'Đặt phòng thành công!')

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;700&display=swap" rel="stylesheet">
<style>
    .success-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,.1);
        padding: 40px;
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
    }
    .booking-code {
        background: #9A7335;
        color: #fff;
        padding: 12px 30px;
        border-radius: 8px;
        display: inline-block;
        margin: 10px 0;
        font-size: 1.2rem;
        font-weight: 600;
    }
    .booking-details {
        background: #FAF8F3;
        padding: 20px;
        border-radius: 8px;
        margin: 20px 0;
        text-align: left;
    }
    .detail-block {
        background: #fff;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    .detail-block h5 {
        background: #000;
        color: #fff;
        padding: 8px;
        border-radius: 4px;
        font-size: 1rem;
        margin-bottom: 10px;
    }
    .detail-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        font-size: .95rem;
    }
    .detail-item .label { font-weight: 500; color: #4b5563; }
    .detail-item .value { font-weight: 600; color: #111827; }
    .qr-code {
        max-width: 200px;
        margin: 20px auto;
        position: relative;
    }
    .qr-code img {
        width: 100%;
        height: auto;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    @media print {
        .navbar, footer, .action-buttons, .email-notification, .qr-code { display: none !important; }
        .success-card { box-shadow: none; padding: 20px; }
    }
    @media (max-width: 768px) {
        .success-card { padding: 20px; }
        .detail-item { flex-direction: column; align-items: flex-start; }
        .qr-code { max-width: 150px; }
    }
</style>
@endpush

@section('content')
<div class="py-4">
    <div class="success-card">

        <h1 style="font-size:1.8rem;font-weight:700;color:#1e3a8a;">Đặt Phòng Thành Công!</h1>

        <div class="booking-code">
            Mã đặt phòng: <strong>#{{ $booking->id }}</strong>
        </div>

        <div class="email-notification alert alert-info mt-3">
            <i class="bi bi-envelope-check me-2"></i>
            Email xác nhận đã được gửi đến: <strong>{{ $booking->customer_email }}</strong>
        </div>

        <div class="booking-details">
            <h3 style="font-family:'Playfair Display',serif;font-size:1.5rem;margin-bottom:15px;">
                <i class="bi bi-info-circle me-2"></i>Chi Tiết Đặt Phòng
            </h3>

            {{-- Thông tin khách --}}
            <div class="detail-block">
                <h5><i class="bi bi-person me-2"></i>Thông Tin Khách Hàng</h5>
                <div class="detail-item">
                    <span class="label">Họ và Tên</span>
                    <span class="value">{{ $booking->customer_name }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Email</span>
                    <span class="value">{{ $booking->customer_email }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Điện thoại</span>
                    <span class="value">{{ $booking->customer_phone }}</span>
                </div>
            </div>

            {{-- Chi tiết phòng --}}
            <div class="detail-block">
                <h5><i class="bi bi-house-door me-2"></i>Chi Tiết Đặt Phòng</h5>
                @foreach($booking->rooms as $room)
                    <div class="detail-item">
                        <span class="label">Phòng</span>
                        <span class="value">
                            Phòng {{ $room->room_number }}
                            @if($room->roomType)
                                <span class="text-muted">({{ $room->roomType->name }})</span>
                            @endif
                        </span>
                    </div>
                @endforeach
                <div class="detail-item">
                    <span class="label">Ngày Nhận Phòng</span>
                    <span class="value">{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Ngày Trả Phòng</span>
                    <span class="value">{{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</span>
                </div>
                <div class="detail-item">
                    <span class="label">Số Khách</span>
                    <span class="value">{{ $booking->adult_count }} người lớn, {{ $booking->child_count }} trẻ em</span>
                </div>
                <div class="detail-item">
                    <span class="label">Tổng Tiền</span>
                    <span class="value text-primary fw-bold">{{ number_format($booking->total_price, 0, ',', '.') }} VNĐ</span>
                </div>
            </div>
        </div>

        @if(!empty($qr_base64))
            <div class="qr-code">
                <h4 class="mb-3 fw-bold" style="font-size: 1.1rem; color: #111827;">Mã QR Đặt Phòng</h4>
                <img src="data:image/png;base64,{{ $qr_base64 }}" alt="Mã QR đặt phòng" loading="lazy">
                <p class="text-muted small mt-2">Dùng mã QR này để check-in nhanh tại quầy lễ tân</p>
            </div>
        @endif

        <div class="action-buttons mt-4 d-flex gap-3 justify-content-center flex-wrap">
            <a href="{{ route('home') }}" class="btn btn-outline-warning">
                <i class="bi bi-house me-2"></i>Về Trang Chủ
            </a>
            <a href="{{ route('booking.mine') }}" class="btn btn-primary">
                <i class="bi bi-calendar-check me-2"></i>Xem Đặt Phòng Của Tôi
            </a>
            <button class="btn btn-secondary" onclick="window.print()">
                <i class="bi bi-printer me-2"></i>In Thông Tin
            </button>
        </div>
    </div>
</div>
@endsection