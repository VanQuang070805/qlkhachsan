@extends('layouts.main')

@section('title', 'Thanh toán · Posh Boutique')

@section('content')
@php
    $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) ?: 1;
    $firstRoom = $booking->rooms->first();
    $roomTypeName = $firstRoom?->roomType?->type_name ?? 'Grand Ocean Panorama Suite';
    $roomImage = !empty($firstRoom?->roomType?->image) ? asset($firstRoom->roomType->image) : asset('images/rooms/1.jpg');
    $roomNumbers = $booking->rooms->map(fn($r) => 'Phòng '.$r->room_number)->join(', ');
    $initials = collect(explode(' ', $booking->customer_name))->map(fn($part) => mb_substr($part, 0, 1))->take(2)->join('');
    $usdPrice = round($booking->total_price / 25400, 2);
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
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill px-3 py-1.5 border shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 600; background: #f8fafc; color: #1e293b; border-color: #cbd5e1 !important;">
                    <i class="bi bi-lock-fill text-primary"></i> 256-bit SSL
                </span>
                <span class="badge rounded-pill px-3 py-1.5 border shadow-xs d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 600; background: #ecfdf5; color: #047857; border-color: #a7f3d0 !important;">
                    <i class="bi bi-shield-check text-emerald-600"></i> PCI-DSS
                </span>
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
                        <input type="radio" name="payment_method" id="method_vietqr" value="vietqr" checked style="display:none;">
                        <span class="aeth-radio-indicator"></span>
                        <div class="aeth-payment-info">
                            <div class="aeth-payment-title-row">
                                <span class="aeth-payment-title">Chuyển khoản VietQR 24/7</span>
                                <span class="badge bg-emerald-100 text-emerald-800 rounded-pill px-2 py-0.5" style="font-size: 10px; font-weight: 700;">Tự động 3s</span>
                            </div>
                            <div class="aeth-payment-desc">Quét QR qua mọi ứng dụng ngân hàng (Napas 247)</div>
                        </div>
                        <div class="aeth-payment-logo-box">
                            <img src="{{ asset('images/payment-methods/vietqr.png') }}" alt="VietQR" onerror="this.src='{{ asset('images/payment-methods/vietqr.svg') }}'">
                        </div>
                    </label>

                    {{-- Phương thức 2: VNPay & Thẻ quốc tế --}}
                    <label class="aeth-payment-choice" for="method_vnpay">
                        <input type="radio" name="payment_method" id="method_vnpay" value="vnpay" style="display:none;">
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
                        <input type="radio" name="payment_method" id="method_momo" value="momo" style="display:none;">
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
                    <label class="aeth-payment-choice" for="method_zalopay">
                        <input type="radio" name="payment_method" id="method_zalopay" value="zalopay" style="display:none;">
                        <span class="aeth-radio-indicator"></span>
                        <div class="aeth-payment-info">
                            <div class="aeth-payment-title-row">
                                <span class="aeth-payment-title">Ví ZaloPay</span>
                            </div>
                            <div class="aeth-payment-desc">Thanh toán liền mạch với ứng dụng Zalo &amp; ZaloPay</div>
                        </div>
                        <div class="aeth-payment-logo-box">
                            <img src="{{ asset('images/payment-methods/zalopay.png') }}" alt="ZaloPay" onerror="this.src='{{ asset('images/payment-methods/zalopay.svg') }}'">
                        </div>
                    </label>

                    {{-- Lưu an toàn vào Apple Keychain --}}
                    <div class="d-flex align-items-center gap-2 mt-3 mb-4 ps-1">
                        <input type="checkbox" id="saveKeychain" class="form-check-input mt-0" checked style="cursor:pointer; width:16px; height:16px;">
                        <label for="saveKeychain" class="text-slate-600 mb-0 d-flex align-items-center gap-1.5" style="font-size: 12.5px; cursor:pointer;">
                            <i class="bi bi-key-fill text-amber-500"></i>
                            <span>Lưu an toàn vào Apple Keychain để thanh toán nhanh lần sau</span>
                        </label>
                    </div>

                    {{-- Nút thanh toán chính --}}
                    <button type="submit" class="aeth-btn-primary" id="payBtn" style="height: 52px; font-size: 16px;">
                        <i class="bi bi-lock-fill"></i>
                        <span>Xác nhận &amp; Thanh toán</span>
                    </button>
                    <p id="methodError" class="text-danger small text-center mt-2" hidden>Vui lòng chọn một phương thức thanh toán.</p>

                    {{-- 3 Huy hiệu bảo chứng chân trang --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-center gap-4 mt-4 pt-2 text-slate-500" style="font-size: 11.5px; font-weight: 500;">
                        <span class="d-inline-flex align-items-center gap-1 text-emerald-700">
                            <i class="bi bi-shield-check"></i> Bảo mật FIDO2
                        </span>
                        <span class="d-inline-flex align-items-center gap-1 text-primary">
                            <i class="bi bi-fingerprint"></i> Face ID / Touch ID
                        </span>
                        <span class="d-inline-flex align-items-center gap-1">
                            <i class="bi bi-arrow-repeat"></i> Hoàn 100% trước 48h
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
                                    VILLA BIỆT LẬP HƯỚNG ĐỒI
                                </small>
                                <strong style="display:block; font-size: 17px; color: #0f172a; margin-top: 1px;">
                                    {{ $roomTypeName }}
                                </strong>
                                <span class="text-slate-500" style="font-size: 13px;">
                                    {{ $roomNumbers ?: 'Posh Suite' }} • {{ $nights }} đêm lưu trú
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
                                <span class="text-slate-500" style="font-size: 12px;">Thứ {{ \Carbon\Carbon::parse($booking->check_in)->dayOfWeek + 1 }} (14:00)</span>
                            </div>
                            <div>
                                <small class="text-muted text-uppercase fw-bold" style="font-size: 11px; letter-spacing: 0.05em; display:block;">TRẢ PHÒNG</small>
                                <strong style="font-size: 15px; color: #0f172a; display:block; margin-top: 2px;">
                                    {{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}
                                </strong>
                                <span class="text-slate-500" style="font-size: 12px;">Chủ Nhật (12:00)</span>
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

                        {{-- Bảng giá & Voucher SUMMER2026 --}}
                        <div class="aeth-breakdown">
                            <div class="aeth-breakdown-row">
                                <span style="font-size: 14.5px;">Giá phòng ({{ $nights }} đêm)</span>
                                <strong style="font-size: 15.5px;">{{ number_format($booking->total_price, 0, ',', '.') }}đ</strong>
                            </div>

                            <div class="pt-2" style="border-top: none !important;">
                                <div class="d-flex align-items-baseline justify-content-between">
                                    <span style="font-size: 15px; font-weight: 700; color: #334155;">Tổng tiền thanh toán</span>
                                    <div class="text-end">
                                        <div style="font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.03em;">
                                            {{ number_format($booking->total_price, 0, ',', '.') }}đ
                                        </div>
                                        <small class="text-muted" style="font-size: 12px;">≈ ${{ number_format($usdPrice, 2) }} USD</small>
                                    </div>
                                </div>
                                <small class="text-slate-400 d-block mt-1" style="font-size: 12px;">Giá lưu trú theo thời gian đã chọn</small>
                            </div>
                        </div>

                        {{-- Thẻ Bảo Hiểm TravelShield™ --}}
                        <div style="background:#ecfdf5; border:1px solid rgba(167,243,208,0.8); border-radius:16px; padding:12px 14px; display:flex; align-items:center; gap:10px;">
                            <i class="bi bi-shield-fill-check text-emerald-600" style="font-size: 20px; flex-shrink: 0;"></i>
                            <div>
                                <strong style="display:block; font-size: 12px; color: #065f46;">Bảo hiểm lưu trú TravelShield™</strong>
                                <small style="font-size: 10.5px; color: #047857;">Được bảo vệ hủy phòng khẩn cấp miễn phí trước 48h.</small>
                            </div>
                        </div>

                        {{-- Hỗ trợ hotline 24/7 --}}
                        <div class="d-flex align-items-center justify-content-between pt-1 text-slate-500" style="font-size: 11.5px;">
                            <span>Cần hỗ trợ thanh toán?</span>
                            <a href="tel:19008899" class="text-decoration-none fw-semibold text-primary d-inline-flex align-items-center gap-1">
                                <i class="bi bi-telephone"></i> Hotline 1900 8899
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

    choices.forEach(choice => {
        choice.addEventListener('click', () => {
            choices.forEach(c => c.classList.remove('is-selected'));
            choice.classList.add('is-selected');
            const radio = choice.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
                const method = radio.value;
                const text = 'Xác nhận & Thanh toán';
                button.innerHTML = `<i class="bi bi-lock-fill"></i> <span>${text}</span>`;
            }
            if (error) error.hidden = true;
        });
    });

    // Countdown Timer (10:00 down to 00:00)
    let timeLeft = 10 * 60;
    const timerEl = document.getElementById('countdownTimer');
    const interval = setInterval(() => {
        if (timeLeft <= 0) {
            clearInterval(interval);
            if (timerEl) timerEl.textContent = '00:00';
            return;
        }
        timeLeft--;
        const m = Math.floor(timeLeft / 60).toString().padStart(2, '0');
        const s = (timeLeft % 60).toString().padStart(2, '0');
        if (timerEl) timerEl.textContent = `${m}:${s}`;
    }, 1000);

    form.addEventListener('submit', event => {
        if (!form.querySelector('input[name="payment_method"]:checked')) {
            event.preventDefault();
            if (error) error.hidden = false;
            return;
        }
        if (form.dataset.preview === 'true') {
            event.preventDefault();
            alert('Đây là chế độ xem trước giao diện. Giao dịch thật không được thực hiện.');
        }
    });
});
</script>
@endpush
@endsection
