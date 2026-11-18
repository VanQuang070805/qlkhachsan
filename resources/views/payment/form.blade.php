@extends('layouts.main')

@section('title', 'Thanh toán · Rosaliza Hotel')

@section('content')
@php
    $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) ?: 1;
    $firstRoom = $booking->rooms->first();
    $roomTypeNames = $booking->rooms->map(fn($room) => $room->roomType?->type_name)->filter()->unique()->values();
    $roomTypeName = $roomTypeNames->join(' · ') ?: 'Hạng phòng đang được cập nhật';
    $roomImage = $firstRoom?->roomType?->image_url ?? asset('images/rooms/default.jpg');
    $roomNumbers = $booking->rooms->map(fn($r) => 'Phòng '.$r->room_number)->join(', ');
    $initials = collect(explode(' ', $booking->customer_name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('');
    $holdRemainingSeconds = !empty($isPreview)
        ? 600
        : max(0, (int) now()->diffInSeconds($booking->paymentHoldTimestamp()->copy()->addMinutes(10), false));
@endphp

<div class="aeth-canvas">
    <div class="aeth-spatial-7xl aeth-animate-in">

        {{-- ── 1. Top Header: Stepper centered giống trang 2 + Live Countdown ── --}}
        <div class="text-center mb-4">
            <div class="aeth-step-capsule">
                <a href="{{ route('booking.create', ['room_ids'=>$booking->rooms->pluck('id')->all(),'check_in'=>$booking->check_in->format('Y-m-d'),'check_out'=>$booking->check_out->format('Y-m-d'),'adults'=>$booking->adult_count,'children'=>$booking->child_count]) }}"
                   class="aeth-capsule-item is-done" style="text-decoration:none;">
                    <span class="aeth-capsule-num"><i class="bi bi-check"></i></span>
                    <span>1. Thông tin khách</span>
                </a>
                <span class="aeth-capsule-arrow">→</span>
                <div class="aeth-capsule-item is-active" aria-current="step">
                    <span class="aeth-capsule-num">2</span>
                    <span>2. Thanh toán</span>
                </div>
            </div>

            <div class="mt-2.5">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-amber-50 border border-amber-200 text-amber-800 shadow-xs" style="font-size: 11.5px; font-weight: 600;">
                    <i class="bi bi-hourglass-split text-amber-600"></i>
                    <span>GIỮ PHÒNG:</span>
                    <strong class="font-monospace text-amber-900" id="countdownTimer">10:00</strong>
                </div>
            </div>
        </div>

        @if(!empty($isPreview))
        <div class="alert alert-info rounded-4 border-info-subtle shadow-xs mb-4" role="status">
            <i class="bi bi-eye me-2"></i> Chế độ xem trước giao diện · Thanh toán thật đang được khóa
        </div>
        @endif

        {{-- ── 2. Tiêu đề trang & Security Badges ── --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-2">
            <div>
                <h1 style="font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.025em; margin-bottom: 4px;">
                    Phương thức thanh toán
                </h1>
                <p class="text-slate-500 m-0" style="font-size: 13.5px;">
                    Chọn phương thức thuận tiện và bảo mật nhất để hoàn tất kỳ nghỉ.
                </p>
            </div>
        </div>

        {{-- ── 3. Layout Grid 12 cột: Trái (Thanh toán 7) - Phải (Biên lai 5) (gap-8: 32px) ── --}}
        <div class="row g-4 items-start" style="--bs-gutter-x: 32px; row-gap: 32px;">

            {{-- ══ CỘT TRÁI (PHƯƠNG THỨC THANH TOÁN 7 COLS) ══ --}}
            <div class="col-lg-7">
                <form action="{{ !empty($isPreview) ? '#' : route('payment.update', $booking->id) }}" method="POST" id="paymentForm" data-preview="{{ !empty($isPreview) ? 'true' : 'false' }}">
                    @csrf
                    @method('PATCH')

                    {{-- Phương thức 1: VietQR 24/7 --}}
                    <label class="aeth-payment-choice is-selected" for="method_vietqr">
                        <input type="radio" name="payment_method" id="method_vietqr" value="vietqr" checked class="visually-hidden">
                        <span class="aeth-radio-indicator"></span>
                        <div class="aeth-payment-info">
                            <div class="aeth-payment-title-row">
                                <span class="aeth-payment-title">Chuyển khoản VietQR 24/7</span>
                                <span class="badge bg-emerald-100 text-emerald-800 rounded-pill px-2 py-0.5" style="font-size: 10px; font-weight: 700;">Chuyển khoản</span>
                            </div>
                            <div class="aeth-payment-desc">Quét QR qua mọi ứng dụng ngân hàng (Napas 247)</div>
                        </div>
                        <div class="aeth-payment-logo-box">
                            <img src="{{ asset('images/payment-methods/vietqr.png') }}" alt="VietQR" onerror="this.src='{{ asset('images/payment-methods/vietqr.svg') }}'">
                        </div>
                    </label>

                    {{-- Phương thức 2: VNPay & Thẻ quốc tế --}}
                    <label class="aeth-payment-choice" for="method_vnpay">
                        <input type="radio" name="payment_method" id="method_vnpay" value="vnpay" class="visually-hidden">
                        <span class="aeth-radio-indicator"></span>
                        <div class="aeth-payment-info">
                            <div class="aeth-payment-title-row">
                                <span class="aeth-payment-title">Thẻ quốc tế &amp; VNPay (Visa, MC, JCB)</span>
                            </div>
                            <div class="aeth-payment-desc">Bảo mật chuẩn 3D-Secure &amp; cổng thanh toán quốc gia VNPay</div>
                        </div>
                        <div class="aeth-payment-logo-box">
                            <img src="{{ asset('images/payment-methods/vnpay.png') }}" alt="VNPay" onerror="this.src='{{ asset('images/payment-methods/vnpay.svg') }}'">
                        </div>
                    </label>

                    {{-- Phương thức 3: Ví MoMo --}}
                    <label class="aeth-payment-choice" for="method_momo">
                        <input type="radio" name="payment_method" id="method_momo" value="momo" class="visually-hidden">
                        <span class="aeth-radio-indicator"></span>
                        <div class="aeth-payment-info">
                            <div class="aeth-payment-title-row">
                                <span class="aeth-payment-title">Ví MoMo</span>
                                <span class="aeth-payment-badge">Phổ biến</span>
                            </div>
                            <div class="aeth-payment-desc">Thanh toán tức thì qua ứng dụng ví điện tử MoMo</div>
                        </div>
                        <div class="aeth-payment-logo-box">
                            <img src="{{ asset('images/payment-methods/momo.png') }}" alt="MoMo" onerror="this.src='{{ asset('images/payment-methods/momo.svg') }}'">
                        </div>
                    </label>

                    {{-- Phương thức 4: Ví ZaloPay --}}
                    <label class="aeth-payment-choice mb-4" for="method_zalopay">
                        <input type="radio" name="payment_method" id="method_zalopay" value="zalopay" class="visually-hidden">
                        <span class="aeth-radio-indicator"></span>
                        <div class="aeth-payment-info">
                            <div class="aeth-payment-title-row">
                                <span class="aeth-payment-title">Ví ZaloPay</span>
                            </div>
                            <div class="aeth-payment-desc">Thanh toán trực tuyến qua ứng dụng Zalo &amp; ZaloPay</div>
                        </div>
                        <div class="aeth-payment-logo-box">
                            <img src="{{ asset('images/payment-methods/zalopay.png') }}" alt="ZaloPay" onerror="this.src='{{ asset('images/payment-methods/zalopay.svg') }}'">
                        </div>
                    </label>

                    {{-- Nút thanh toán chính --}}
                    <button type="submit" class="aeth-btn-primary" id="payBtn" style="height: 52px; font-size: 16px;">
                        <i class="bi bi-lock-fill"></i>
                        <span>Xác nhận &amp; Thanh toán</span>
                    </button>
                    <p id="methodError" class="text-danger small text-center mt-2" hidden>Vui lòng chọn một phương thức thanh toán.</p>

                    {{-- 3 Huy hiệu bảo chứng chân trang --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-4 mt-4 pt-2 text-slate-500" style="font-size: 11.5px; font-weight: 500;">
                        <span class="d-inline-flex align-items-center gap-1">
                            <i class="bi bi-arrow-repeat"></i> Hạn xét hoàn tiền: trước ngày nhận phòng {{ $booking->refundDeadlineDays() }} ngày
                        </span>
                    </div>

                </form>
            </div>

            {{-- ══ CỘT PHẢI (HÓA ĐƠN BIÊN LAI CHI TIẾT 5 COLS - Ảnh 5) ══ --}}
            <div class="col-lg-5">
                <div class="aeth-sticky-sidebar">
                    <div class="aeth-booking-box">

                        {{-- Đầu biên lai --}}
                        <div class="d-flex align-items-center justify-content-between pb-3 border-bottom">
                            <span style="font-size: 13.5px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #475569;">
                                <i class="bi bi-receipt-cutoff me-1"></i> CHI TIẾT ĐẶT PHÒNG
                            </span>
                            <span class="badge rounded-pill bg-emerald-50 text-emerald-700 px-3 py-1.5 border border-emerald-200/80" style="font-size: 12px; font-weight: 600;">
                                <span class="aeth-pulse-dot me-1" style="width:6px; height:6px;"></span> Đang giữ phòng
                            </span>
                        </div>

                        {{-- Thẻ media phòng thu nhỏ --}}
                        <div class="d-flex align-items-center gap-3">
                            <img src="{{ $roomImage }}" alt="{{ $roomTypeName }}" style="width: 76px; height: 76px; border-radius: 16px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                            <div>
                                <small style="display:block; font-size: 11px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--apple-blue);">
                                    HẠNG PHÒNG
                                </small>
                                <strong style="display:block; font-size: 17px; color: #0f172a; margin-top: 1px;">
                                    {{ $roomTypeName }}
                                </strong>
                                <span class="text-slate-500" style="font-size: 13px;">
                                    {{ $roomNumbers ?: 'Chưa gán số phòng' }} • {{ $nights }} đêm lưu trú
                                </span>
                            </div>
                        </div>

                        {{-- Khung ngày Nhận / Trả phòng kép --}}
                        <div style="background: #f8fafc; border: 1px solid #f1f5f9; border-radius: 16px; padding: 14px 16px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.05em; display:block;">NHẬN PHÒNG</small>
                                <strong style="font-size: 15px; color: #0f172a; display:block; margin-top: 2px;">
                                    {{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}
                                </strong>
                                <span class="text-slate-600" style="font-size: 12px;">14:00 - trước 18:00</span>
                            </div>
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.05em; display:block;">TRẢ PHÒNG</small>
                                <strong style="font-size: 15px; color: #0f172a; display:block; margin-top: 2px;">
                                    {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}
                                </strong>
                                <span class="text-slate-500" style="font-size: 12px;">Trước 12:00</span>
                            </div>
                        </div>

                        {{-- Khung thông tin khách có nút Sửa --}}
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border border-slate-100">
                            <div class="d-flex align-items-center gap-2.5">
                                <div class="aeth-author-avatar" style="width:36px; height:36px; font-size:12px; font-weight:700;">
                                    {{ $initials ?: 'HM' }}
                                </div>
                                <div>
                                    <strong style="display:block; font-size: 14px; color: #0f172a;">{{ $booking->customer_name }}</strong>
                                    <small class="text-slate-500" style="font-size: 12px;">{{ $booking->customer_email }}</small>
                                </div>
                            </div>
                            <a href="{{ route('booking.create', ['room_ids'=>$booking->rooms->pluck('id')->all(),'check_in'=>$booking->check_in->format('Y-m-d'),'check_out'=>$booking->check_out->format('Y-m-d'),'adults'=>$booking->adult_count,'children'=>$booking->child_count]) }}"
                               class="text-primary fw-semibold" style="font-size: 13px; text-decoration: none;">
                                Sửa
                            </a>
                        </div>

                        {{-- Bảng giá --}}
                        <div class="aeth-breakdown">
                            <div class="aeth-breakdown-row">
                                <span style="font-size: 14.5px;">Giá phòng ({{ $nights }} đêm)</span>
                                <span style="font-size: 15.5px; font-weight: 500; font-variant-numeric: tabular-nums; color: #0f172a;">{{ number_format($booking->total_price, 0, ',', '.') }}đ</span>
                            </div>

                            <div class="pt-2" style="border-top: none !important;">
                                <div class="d-flex align-items-baseline justify-content-between gap-2">
                                    <span style="font-size: 14.5px; font-weight: 600; color: #334155;">Tiền cọc thanh toán lần này</span>
                                    <div class="text-end">
                                        <div style="font-size: 22px; font-weight: 600; font-variant-numeric: tabular-nums; color: #0f172a; letter-spacing: -0.02em;">
                                            {{ number_format($booking->deposit_amount, 0, ',', '.') }}đ
                                        </div>
                                    </div>
                                </div>
                                <small class="text-slate-400 d-block mt-1" style="font-size: 12px;">Khoản 50% còn lại thanh toán tại khách sạn khi làm thủ tục.</small>
                            </div>
                        </div>

                        {{-- Điều kiện hoàn tiền theo đơn --}}
                        <div style="background:#ecfdf5; border:1px solid rgba(167,243,208,0.8); border-radius:16px; padding:12px 14px; display:flex; align-items:center; gap:10px;">
                            <i class="bi bi-shield-fill-check text-emerald-600" style="font-size: 20px; flex-shrink: 0;" aria-hidden="true"></i>
                            <div>
                                <strong style="display:block; font-size: 12px; color: #065f46;">Điều kiện hoàn tiền</strong>
                                <small style="font-size: 11px; color: #047857;">Thời hạn xét hoàn: trước ngày nhận phòng {{ $booking->refundDeadlineDays() }} ngày.</small>
                            </div>
                        </div>

                        {{-- Hỗ trợ hotline 24/7 --}}
                        <div class="d-flex align-items-center justify-content-between pt-1 text-slate-500" style="font-size: 11.5px;">
                            <span>Cần hỗ trợ thanh toán?</span>
                            <a href="{{ route('contact') }}" class="text-decoration-none fw-semibold text-primary d-inline-flex align-items-center gap-1">
                                <i class="bi bi-chat-dots" aria-hidden="true"></i> Thông tin liên hệ
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('paymentForm');
    const button = document.getElementById('payBtn');
    const error = document.getElementById('methodError');
    const choices = [...document.querySelectorAll('.aeth-payment-choice')];

    const syncSelectedChoice = () => {
        choices.forEach(c => {
            const radio = c.querySelector('input[type="radio"]');
            c.classList.toggle('is-selected', Boolean(radio?.checked));
        });
        button.innerHTML = '<i class="bi bi-lock-fill"></i> <span>Xác nhận &amp; Thanh toán</span>';
        if (error) error.hidden = true;
    };

    choices.forEach(choice => {
        const radio = choice.querySelector('input[type="radio"]');
        radio?.addEventListener('change', syncSelectedChoice);
        choice.addEventListener('click', () => {
            if (radio) {
                radio.checked = true;
                syncSelectedChoice();
            }
        });
    });

    // Server-synchronized Countdown Timer
    let timeLeft = {{ (int) $holdRemainingSeconds }};
    const timerEl = document.getElementById('countdownTimer');
    const renderTimer = () => {
        const safe = Math.max(0, timeLeft);
        const m = Math.floor(safe / 60).toString().padStart(2, '0');
        const s = (safe % 60).toString().padStart(2, '0');
        if (timerEl) timerEl.textContent = `${m}:${s}`;
    };
    renderTimer();
    const interval = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(interval);
            renderTimer();
            return;
        }
        timeLeft--;
        renderTimer();
    }, 1000);

    form.addEventListener('submit', event => {
        if (!form.querySelector('input[name="payment_method"]:checked')) {
            event.preventDefault();
            if (error) error.hidden = false;
            return;
        }
        if (form.dataset.preview === 'true') {
            event.preventDefault();
            if (typeof window.royalToast === 'function') {
                window.royalToast('Đây là chế độ xem trước giao diện. Giao dịch thật không được thực hiện.', 'info');
            }
        }
    });
});
</script>
@endpush
@endsection
