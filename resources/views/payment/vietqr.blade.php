@extends('layouts.main')

@section('title', 'Quét QR Thanh Toán · Posh Boutique')

@section('content')
@php
    $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out) ?: 1;
    $rooms  = $booking->rooms;
@endphp

<div class="aeth-canvas" style="padding-top: 40px; padding-bottom: 60px;">
    <div style="max-width: 960px; margin: 0 auto; padding: 0 16px;">

        {{-- Top Header --}}
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div>
                <a href="{{ route('payment.form', $booking->id) }}" class="text-decoration-none text-slate-500 mb-2 d-inline-flex align-items-center gap-1.5" style="font-size: 13px; font-weight: 600;">
                    <i class="bi bi-arrow-left"></i> Quay lại chọn phương thức
                </a>
                <h1 style="font-size: 26px; font-weight: 800; color: #0f172a; letter-spacing: -0.025em; margin: 0;">
                    Quét mã QR để thanh toán
                </h1>
                <p class="text-slate-500 m-0 mt-1" style="font-size: 13.5px;">
                    Đặt phòng #{{ $booking->id }} · Sử dụng app ngân hàng hoặc ví điện tử bất kỳ để quét mã
                </p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-emerald-50 text-emerald-700 px-3 py-1.5 border border-emerald-200" style="font-size: 12px; font-weight: 600;">
                    <span class="aeth-pulse-dot me-1" style="width: 6px; height: 6px;"></span> Chuyển khoản Napas 247
                </span>
            </div>
        </div>

        {{-- Main 2-Column Grid --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; align-items: start;">

            {{-- ══ CỘT TRÁI: QR CODE CARD (CĂN GIỮA HOÀN TOÀN) ══ --}}
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05); text-align: center;">
                {{-- Header --}}
                <div style="background: linear-gradient(135deg, #0071e3 0%, #38bdf8 100%); padding: 18px 20px; color: #ffffff;">
                    <div style="font-size: 18px; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="bi bi-qr-code-scan"></i> VietQR - Chuyển khoản tự động
                    </div>
                    <div style="font-size: 13px; opacity: 0.9; margin-top: 4px;">Hỗ trợ 40+ ngân hàng Việt Nam &amp; Napas 24/7</div>
                </div>

                {{-- Body (Căn giữa mã QR) --}}
                <div style="padding: 24px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    @if(!empty($qrData['qr_url']))
                        <div style="background: #ffffff; padding: 12px; border-radius: 18px; border: 2px solid #e2e8f0; box-shadow: 0 8px 24px rgba(0, 113, 227, 0.12); display: flex; align-items: center; justify-content: center; margin: 0 auto;">
                            <img src="{{ $qrData['qr_url'] }}"
                                 alt="VietQR Code"
                                 style="width: 250px; height: 250px; max-width: 100%; border-radius: 10px; display: block; margin: 0 auto; object-fit: contain;">
                        </div>
                    @else
                        <div style="width: 250px; height: 250px; background: #f8fafc; border-radius: 18px; border: 2px dashed #cbd5e1; display: flex; flex-direction: column; align-items: center; justify-content: center; margin: 0 auto; gap: 10px;">
                            <i class="bi bi-bank text-primary" style="font-size: 48px;"></i>
                            <span style="font-size: 13px; color: #64748b; font-weight: 500;">Chưa tạo được QR tự động</span>
                        </div>
                    @endif

                    {{-- Nội dung chuyển khoản copy box --}}
                    <div style="width: 100%; margin-top: 20px; background: #f0f7ff; border: 1px solid #bfdbfe; border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; text-align: left;">
                        <div>
                            <div style="font-size: 11.5px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 0.04em;">Nội dung chuyển khoản</div>
                            <code id="refCodeText" style="font-size: 16px; font-weight: 800; color: #1e3a8a; letter-spacing: 0.02em;">{{ $qrData['reference_code'] ?? 'KS'.$booking->id }}</code>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" onclick="copyRefCode()" style="border-radius: 8px; font-size: 12.5px; font-weight: 600; padding: 6px 14px; background: #0071e3; border: none;">
                            <i class="bi bi-clipboard me-1"></i> <span id="copyBtnText">Sao chép</span>
                        </button>
                    </div>

                    {{-- Countdown Timer: 10 phút --}}
                    <div style="width: 100%; background: #fefce8; border: 1px solid #fef08a; padding: 11px 16px; border-radius: 12px; margin-top: 14px; font-size: 13.5px; color: #854d0e; text-align: center; font-weight: 500;">
                        <i class="bi bi-clock-history me-1.5 text-amber-600"></i> Mã hết hạn sau <strong id="countdown" class="font-monospace" style="font-weight: 800; font-size: 15px; color: #713f12;">10:00</strong>
                    </div>

                    {{-- Polling Status --}}
                    <div id="pollStatus" style="margin-top: 16px; font-size: 13px; color: #64748b; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <span class="spinner-border spinner-border-sm text-primary" role="status"></span>
                        <span>Đang chờ xác nhận thanh toán tự động...</span>
                    </div>
                </div>
            </div>

            {{-- ══ CỘT PHẢI: CHI TIẾT SỐ TIỀN & THÔNG TIN ĐẶT PHÒNG ══ --}}
            <div style="display: flex; flex-direction: column; gap: 18px;">

                {{-- Card Số tiền cọc --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); text-align: center;">
                    <div style="color: #64748b; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;">
                        Số tiền đặt cọc (50%)
                    </div>
                    <div style="font-size: 34px; font-weight: 800; color: #0071e3; margin: 8px 0; letter-spacing: -0.03em;">
                        {{ number_format($booking->deposit_amount, 0, ',', '.') }} đ
                    </div>
                    <div style="font-size: 12.5px; color: #64748b; margin-bottom: 8px;">
                        Tổng giá trị: {{ number_format($booking->total_price, 0, ',', '.') }} đ — Phần còn lại thanh toán khi nhận phòng
                    </div>
                    <span class="badge bg-blue-50 text-primary border border-blue-200 rounded-pill px-3 py-1.5" style="font-size: 12px; font-weight: 600;">
                        {{ $nights }} đêm lưu trú · {{ $booking->adult_count }} khách
                    </span>
                </div>

                {{-- Card Thông tin đặt phòng --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                    <div style="font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.05em;">
                        <i class="bi bi-info-circle text-primary me-1"></i> Thông tin đặt phòng
                    </div>
                    @foreach([
                        'Khách hàng' => $booking->customer_name,
                        'Phòng lưu trú' => $rooms->map(fn($r) => 'P.'.$r->room_number)->join(', '),
                        'Nhận phòng' => \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') . ' (14:00)',
                        'Trả phòng'  => \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') . ' (12:00)',
                    ] as $label => $val)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 9px 0; border-bottom: 1px solid #f1f5f9; font-size: 13.5px;">
                            <span style="color: #64748b;">{{ $label }}</span>
                            <span style="font-weight: 700; color: #0f172a;">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Card Hướng dẫn 4 bước --}}
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 22px; box-shadow: 0 4px 20px rgba(0,0,0,0.03);">
                    <div style="font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 14px; text-transform: uppercase; letter-spacing: 0.05em;">
                        <i class="bi bi-lightning-charge text-amber-500 me-1"></i> Hướng dẫn thanh toán nhanh
                    </div>
                    @foreach([
                        ['🏦', 'Mở ứng dụng ngân hàng', 'Chọn Quét mã QR hoặc Chuyển khoản nhanh Napas 247'],
                        ['📷', 'Quét mã QR',           'Hướng camera điện thoại vào mã QR ở khung bên trái'],
                        ['✅', 'Kiểm tra & Xác nhận',  'Số tiền và nội dung chuyển khoản được tự động điền sẵn'],
                        ['⚡', 'Xác nhận tức thì',     'Hệ thống tự động kích hoạt phòng và gửi mã QR nhận phòng'],
                    ] as [$icon, $title, $desc])
                        <div style="display: flex; gap: 12px; margin-bottom: 12px; align-items: flex-start;">
                            <div style="width: 34px; height: 34px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0;">
                                {{ $icon }}
                            </div>
                            <div>
                                <div style="font-weight: 700; font-size: 13.5px; color: #0f172a;">{{ $title }}</div>
                                <div style="color: #64748b; font-size: 12px; margin-top: 1px;">{{ $desc }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Nút Đổi phương thức --}}
                <div style="display: flex; justify-content: center;">
                    <a href="{{ route('payment.form', $booking->id) }}" class="btn btn-outline-secondary px-4 py-2.5" style="border-radius: 12px; font-size: 13.5px; font-weight: 600; width: 100%; text-align: center;">
                        ← Đổi phương thức thanh toán khác
                    </a>
                </div>

            </div>

        </div>

        {{-- Footer Note --}}
        <div class="alert alert-light border mt-4 text-center rounded-4 shadow-xs" style="font-size: 12.5px; color: #64748b;">
            <i class="bi bi-shield-lock-fill text-primary me-1"></i>
            Hệ thống thanh toán bảo mật liên kết Napas 24/7. Nếu sau 10 phút chưa nhận được email xác nhận, vui lòng liên hệ hotline <strong>1900 8899</strong> để được hỗ trợ.
        </div>

    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── Copy Reference Code ──────────────────────────────
    function copyRefCode() {
        const code = document.getElementById('refCodeText').innerText.trim();
        navigator.clipboard.writeText(code).then(() => {
            const btnText = document.getElementById('copyBtnText');
            btnText.innerText = 'Đã chép!';
            setTimeout(() => { btnText.innerText = 'Sao chép'; }, 2000);
        });
    }

    // ── Countdown 10 phút (600s) ──────────────────────────
    const timerKey = 'vietqr_timer_{{ $booking->id }}';
    if (!localStorage.getItem(timerKey)) {
        localStorage.setItem(timerKey, Date.now().toString());
    }
    let time = Math.max(0, 600 - Math.floor((Date.now() - parseInt(localStorage.getItem(timerKey))) / 1000));

    const countdownEl = document.getElementById('countdown');
    const fmt = t => Math.floor(t/60) + ':' + (t%60 < 10 ? '0' : '') + (t%60);
    countdownEl.textContent = fmt(time);

    const countdownTimer = setInterval(function () {
        time--;
        if (time <= 0) {
            clearInterval(countdownTimer);
            clearInterval(pollTimer);
            localStorage.removeItem(timerKey);
            document.getElementById('pollStatus').innerHTML =
                '<div class="alert alert-danger mt-2 py-2">⚠️ Mã QR đã hết hiệu lực. <button class="btn btn-sm btn-primary ms-2" onclick="location.reload()">Tạo lại mã</button></div>';
            countdownEl.textContent = '0:00';
            return;
        }
        countdownEl.textContent = fmt(time);
    }, 1000);

    // ── Polling mỗi 5 giây ────────────────────────────────
    const pollTimer = setInterval(function () {
        fetch('{{ route('payment.check', $booking->id) }}')
            .then(r => r.json())
            .then(data => {
                if (data.status === 'paid') {
                    clearInterval(pollTimer);
                    clearInterval(countdownTimer);
                    localStorage.removeItem(timerKey);
                    document.getElementById('pollStatus').innerHTML =
                        '<span class="text-success fw-bold">✅ Thanh toán thành công! Đang chuyển trang...</span>';
                    window.location.href = data.redirect_url;
                }
            })
            .catch(() => {});
    }, 5000);
</script>
@endpush
