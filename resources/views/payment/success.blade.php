@extends('layouts.main')

@section('title', 'Thanh toán thành công · Posh Boutique')

@push('styles')
<style>
    .booking-success-backdrop {
        position: fixed;
        inset: 0;
        z-index: 1050;
        background: rgba(15, 23, 42, 0.72);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        overflow-y: auto;
        animation: modalFadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    @keyframes modalFadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    @keyframes modalScaleIn {
        from { opacity: 0; transform: scale(0.96) translateY(8px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }
    .booking-card-modal {
        background: #ffffff;
        border-radius: 28px;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
        max-width: 440px;
        width: 100%;
        position: relative;
        padding: 36px 28px 28px;
        animation: modalScaleIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .qr-card-modal {
        background: #ffffff;
        border-radius: 32px;
        box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.35);
        max-width: 400px;
        width: 100%;
        position: relative;
        padding: 32px 24px 24px;
        animation: modalScaleIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
    .modal-close-btn {
        position: absolute;
        top: 18px;
        right: 18px;
        width: 34px;
        height: 34px;
        border-radius: 50%;
        background: #f1f5f9;
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #64748b;
        cursor: pointer;
        transition: all 0.15s ease;
        z-index: 10;
        font-size: 15px;
    }
    .modal-close-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .check-circle-icon {
        width: 52px;
        height: 52px;
        border-radius: 50%;
        background: #d1fae5;
        color: #10b981;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 14px;
    }
    .qr-squircle-icon {
        width: 56px;
        height: 56px;
        border-radius: 18px;
        background: #eff6ff;
        color: #0071e3;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 12px;
    }
    .detail-inset-box {
        background: #f8fafc;
        border: 1px solid #edf2f7;
        border-radius: 16px;
        padding: 14px 18px;
        margin-bottom: 20px;
        text-align: left;
    }
    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid #f1f5f9;
        font-size: 13px;
    }
    .detail-row:last-child {
        border-bottom: none;
        padding-top: 10px;
        padding-bottom: 2px;
    }
    .detail-row .label {
        color: #94a3b8;
        font-weight: 500;
    }
    .detail-row .value {
        color: #0f172a;
        font-weight: 600;
        text-align: right;
    }
    .btn-apple-wallet {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        height: 46px;
        border-radius: 14px;
        background: #0f172a;
        color: #ffffff;
        font-size: 13.5px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        margin-bottom: 10px;
    }
    .btn-apple-wallet:hover {
        background: #1e293b;
        transform: translateY(-1px);
    }
    .btn-action-checkin {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 44px;
        border-radius: 14px;
        background: #eff6ff;
        color: #0071e3;
        font-size: 13.5px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        width: 100%;
    }
    .btn-action-checkin:hover {
        background: #dbeafe;
    }
    .btn-action-history {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        height: 44px;
        border-radius: 14px;
        background: #f1f5f9;
        color: #475569;
        font-size: 13.5px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        width: 100%;
    }
    .btn-action-history:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .btn-qr-done {
        width: 100%;
        height: 46px;
        border-radius: 14px;
        background: #0071e3;
        color: #ffffff;
        font-size: 15px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .btn-qr-done:hover {
        background: #005bb5;
    }
    @media print {
        body * { visibility: hidden; }
        .booking-card-modal, .booking-card-modal * { visibility: visible; }
        .booking-success-backdrop { position: static; background: none; padding: 0; }
        .booking-card-modal { box-shadow: none; border: none; max-width: 100%; }
        .modal-close-btn, .btn-apple-wallet, .btn-action-checkin, .btn-action-history { display: none !important; }
    }
</style>
@endpush

@section('content')
@php
    $nights = 1;
    if (!empty($booking->check_in) && !empty($booking->check_out)) {
        try {
            $nights = max(1, \Carbon\Carbon::parse($booking->check_in)->diffInDays(\Carbon\Carbon::parse($booking->check_out)));
        } catch (\Throwable $e) {}
    }

    $room_name = 'Phòng Nghỉ Posh';
    if (isset($booking->rooms) && $booking->rooms->isNotEmpty()) {
        $roomTypes = $booking->rooms->map(fn($r) => $r->roomType?->name ?? ('Phòng ' . $r->room_number))->unique()->filter();
        if ($roomTypes->isNotEmpty()) {
            $room_name = $roomTypes->first();
        }
    } elseif (!empty($booking->type_name)) {
        $room_name = $booking->type_name;
    }

    if (empty($qr_base64) && !empty($checkin_token)) {
        try {
            $qrObj = \Endroid\QrCode\QrCode::create($checkin_token)
                ->setSize(240)
                ->setMargin(8);
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $qr_base64 = base64_encode($writer->write($qrObj)->getString());
        } catch (\Throwable $e) {
            $qr_base64 = null;
        }
    }
@endphp

<div class="booking-success-backdrop" id="bookingSuccessBackdrop" onclick="handleBackdropClick(event)">

    {{-- MODAL 1: BỐ CỤC XÁC NHẬN ĐẶT PHÒNG (ẢNH 1) --}}
    <div class="booking-card-modal text-center" id="successModal" role="dialog" aria-modal="true">
        <a href="{{ route('booking.mine') }}" class="modal-close-btn" aria-label="Đóng popup">
            <i class="bi bi-x-lg"></i>
        </a>

        {{-- Checkmark Icon (Không viền tròn bao quanh) --}}
        <div style="display: flex; justify-content: center; margin-bottom: 12px;">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>

        {{-- Tag --}}
        <div style="font-size: 12px; font-weight: 700; color: #0071e3; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 6px;">
            ĐẶT PHÒNG THÀNH CÔNG
        </div>

        {{-- Room Title --}}
        <h2 style="font-size: 21px; font-weight: 800; color: #0f172a; margin-bottom: 6px; line-height: 1.3;">
            {{ $room_name }}
        </h2>

        {{-- Subtitle --}}
        <p style="font-size: 12.5px; color: #64748b; line-height: 1.45; margin-bottom: 22px;">
            Xác nhận đặt phòng đã được mã hoá và gửi tới email của Quý khách.
        </p>

        {{-- Inset Details Box (Bỏ đón tiễn VIP) --}}
        <div class="detail-inset-box">
            <div class="detail-row">
                <span class="label">Mã đặt phòng:</span>
                <span class="value" style="font-weight: 700;">#{{ $booking->id }}</span>
            </div>
            <div class="detail-row">
                <span class="label">Thời gian lưu trú:</span>
                <span class="value">
                    {{ \Carbon\Carbon::parse($booking->check_in)->format('d \T\hm') }} — {{ \Carbon\Carbon::parse($booking->check_out)->format('d \T\hm') }} ({{ $nights }} đêm)
                </span>
            </div>
            <div class="detail-row">
                <span class="label">Người lưu trú:</span>
                <span class="value">{{ $booking->customer_name }}</span>
            </div>
            <div class="detail-row">
                <span class="label" style="font-weight: 700; color: #0f172a;">Đã thanh toán:</span>
                <span class="value" style="color: #0071e3; font-weight: 800; font-size: 15px;">
                    {{ number_format($booking->total_price, 0, ',', '.') }}đ
                </span>
            </div>
        </div>

        {{-- Nút Thêm vào Apple Wallet --}}
        <button type="button" class="btn-apple-wallet" id="btnAppleWallet" onclick="handleWalletClick()">
            <i class="bi bi-apple fs-6"></i>
            <span id="txtWalletBtn">Thêm thẻ phòng vào Apple Wallet</span>
        </button>

        {{-- Cụm 2 nút: Mã Check-in & Xem Lịch Sử --}}
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            @if($checkin_token)
            <button type="button" class="btn-action-checkin" onclick="openQrModal()">
                <i class="bi bi-qr-code" style="font-size: 15px;"></i>
                <span>Mã Check-in</span>
            </button>
            @endif
            <a href="{{ route('booking.mine') }}" class="btn-action-history">
                <i class="bi bi-clock-history" style="font-size: 15px;"></i>
                <span>Xem Lịch Sử</span>
            </a>
        </div>
    </div>

    {{-- MODAL 2: THẺ CHECK-IN ĐIỆN TỬ (ẢNH 2) --}}
    <div class="qr-card-modal text-center" id="checkinQrModal" style="display: none;" role="dialog" aria-modal="true">
        <button type="button" class="modal-close-btn" onclick="closeQrModal()" aria-label="Đóng mã QR">
            <i class="bi bi-x-lg"></i>
        </button>

        {{-- Top QR Icon (Không viền tròn bao quanh) --}}
        <div style="display: flex; justify-content: center; margin-bottom: 10px;">
            <i class="bi bi-qr-code-scan" style="font-size: 32px; color: #0071e3;"></i>
        </div>

        {{-- Tag --}}
        <div style="font-size: 12px; font-weight: 700; color: #059669; letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 4px;">
            THẺ CHECK-IN ĐIỆN TỬ
        </div>

        {{-- Room Title --}}
        <h3 style="font-size: 20px; font-weight: 800; color: #0f172a; margin-bottom: 4px; line-height: 1.3;">
            {{ $room_name }}
        </h3>

        {{-- ID Code --}}
        <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
            Mã định danh: <strong style="color: #0071e3;">#{{ $booking->id }}</strong>
        </p>

        {{-- QR Card Wrapper --}}
        <div style="border: 1px solid #e2e8f0; border-radius: 24px; padding: 22px 18px 18px; background: #f8fafc; margin-bottom: 18px;">
            <div style="background: #ffffff; border-radius: 18px; padding: 14px; display: inline-block; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
                @php
                    $standardQr = null;
                    if (!empty($qr_base64)) {
                        $standardQr = 'data:image/png;base64,' . $qr_base64;
                    } elseif (!empty($checkin_token)) {
                        try {
                            $qrObj = \Endroid\QrCode\QrCode::create($checkin_token)->setSize(194)->setMargin(2);
                            $standardQr = (new \Endroid\QrCode\Writer\SvgWriter())->write($qrObj)->getDataUri();
                        } catch (\Throwable $e) {
                            $standardQr = null;
                        }
                    }
                @endphp
                @if($standardQr)
                    <img src="{{ $standardQr }}" alt="Mã QR Check-in" style="width: 194px; height: 194px; display: block; border-radius: 8px;">
                @else
                    <p class="small text-muted mb-0">Mã check-in chưa khả dụng. Vui lòng liên hệ lễ tân.</p>
                @endif
            </div>

            <div style="display: inline-block; margin-top: 14px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 9999px; padding: 6px 14px; font-size: 12.5px; font-weight: 600; color: #059669;">
                Mã có hiệu lực đến hết ngày trả phòng ({{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }})
            </div>
        </div>

        {{-- Description text --}}
        <p style="font-size: 12.5px; color: #64748b; line-height: 1.5; margin-bottom: 20px; padding: 0 4px;">
            Quý khách chỉ cần xuất trình mã QR này tại Quầy Lễ Tân hoặc máy quét để kích hoạt chìa khóa phòng.
        </p>

        {{-- Nút Xong --}}
        <button type="button" class="btn-qr-done" onclick="closeQrModal()">
            Xong
        </button>
    </div>

</div>

<script>
    // Khóa cuộn trang khi Popup hiển thị
    document.addEventListener('DOMContentLoaded', () => {
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
        document.body.classList.add('has-modal-open');
    });

    function openQrModal() {
        document.getElementById('successModal').style.display = 'none';
        document.getElementById('checkinQrModal').style.display = 'block';
    }

    function closeQrModal() {
        document.getElementById('checkinQrModal').style.display = 'none';
        document.getElementById('successModal').style.display = 'block';
    }

    function handleBackdropClick(event) {
        if (event.target === document.getElementById('bookingSuccessBackdrop')) {
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            document.body.classList.remove('has-modal-open');
            window.location.href = "{{ route('booking.mine') }}";
        }
    }

    function handleWalletClick() {
        const btn = document.getElementById('btnAppleWallet');
        const txt = document.getElementById('txtWalletBtn');
        if (!btn || !txt) return;

        btn.style.background = '#059669';
        txt.textContent = 'Đã thêm vào Apple Wallet';
        setTimeout(() => {
            btn.style.background = '#0f172a';
            txt.textContent = 'Thêm thẻ phòng vào Apple Wallet';
        }, 3000);
    }
</script>
@endsection
