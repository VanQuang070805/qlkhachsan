@extends('layouts.dashboard')

@section('content')
@php
    $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out);
    $rooms  = $booking->rooms;
@endphp

<main class="main-content">
<div style="max-width:900px;margin:auto;">
    <div class="d-flex align-items-center gap-3 mb-4">
        <a href="{{ route('staff.bookings') }}" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Sơ đồ phòng
        </a>
        <div>
            <h4 class="fw-bold m-0 text-dark">Thanh toán trả phòng</h4>
            <p class="text-muted m-0" style="font-size:0.85rem;">Booking #{{ $booking->id }} · Quét mã để thanh toán số tiền còn lại</p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">

        {{-- QR Card --}}
        <div class="card shadow-sm" style="text-align:center;border-radius:14px;overflow:hidden;">
            <div style="background:linear-gradient(135deg,#1565C0,#42a5f5);padding:20px;color:#fff">
                <div style="font-size:18px;font-weight:700"><i class="fa-solid fa-qrcode me-2"></i>VietQR - Trả phòng</div>
                <div style="font-size:13px;opacity:.85;margin-top:4px">Hỗ trợ 40+ ngân hàng · Booking #{{ $booking->id }}</div>
            </div>
            <div class="card-body">
                @if(!empty($qrData['qr_url']))
                    <img src="{{ $qrData['qr_url'] }}"
                         alt="VietQR Code"
                         style="width:100%;max-width:260px;border-radius:12px;border:3px solid #1565C0">
                @else
                    <div style="width:260px;height:260px;background:#f0f0f0;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:60px">🏦</div>
                    <p style="color:#888;margin-top:12px;font-size:13px">Không tạo được mã QR. Dùng thông tin chuyển khoản bên cạnh.</p>
                @endif

                <div style="margin-top:16px;background:#e3f2fd;border-radius:8px;padding:12px;text-align:left">
                    <div style="font-size:13px;font-weight:700;color:#1565C0;margin-bottom:8px">📋 Nội dung chuyển khoản:</div>
                    <code style="font-size:15px;font-weight:700;color:#0d47a1">{{ $qrData['reference_code'] ?? 'CO'.$booking->id }}</code>
                </div>

                <div style="background:#fff3cd;border:1px solid #ffeeba;padding:10px;border-radius:8px;margin-top:10px;font-size:14px;text-align:center;">
                    Mã QR còn hiệu lực trong  <strong id="countdown">5:00</strong>
                </div>

                {{-- Trạng thái polling --}}
                <div id="pollStatus" style="margin-top:10px;font-size:13px;color:#888;text-align:center">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Đang chờ xác nhận thanh toán trả phòng...
                </div>
            </div>
        </div>

        {{-- Info --}}
        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- Số tiền cần thanh toán --}}
            <div class="card shadow-sm" style="border-radius:14px;overflow:hidden;">
                <div class="card-body" style="text-align:center">
                    <div style="color:#666;font-size:14px;text-transform:uppercase;letter-spacing:.05em;font-weight:600">Số tiền còn lại cần thanh toán</div>
                    <div style="font-size:36px;font-weight:800;color:#dc3545;margin:8px 0">
                        {{ number_format($remaining, 0, ',', '.') }} ₫
                    </div>
                    <div style="font-size:12px;color:#999;margin-bottom:8px">
                        Tổng: {{ number_format($booking->total_price, 0, ',', '.') }} ₫ —
                        Đã đặt cọc: {{ number_format($booking->deposit_amount ?? 0, 0, ',', '.') }} ₫
                        @if($booking->late_checkout_fee > 0)
                           <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
                            <span style="color:#f59e0b">⏰ Phụ thu trả muộn</span>
                            <span style="color:#f59e0b">+ {{ number_format($booking->late_checkout_fee, 0, ',', '.') }} ₫</span>
                        </div>
                        @endif
                    </div>
                    <span class="badge bg-danger rounded-pill px-3">Trả phòng</span>
                </div>
            </div>

            {{-- Chi tiết trả phòng --}}
            <div class="card shadow-sm" style="border-radius:14px;">
                <div class="card-body">
                    <div style="font-size:13px;font-weight:700;color:#666;margin-bottom:10px;text-transform:uppercase">Chi tiết trả phòng</div>
                    @foreach([
                        ['👤', 'Khách hàng',   $booking->customer_name],
                        ['🏨', 'Phòng',         $rooms->map(fn($r) => 'Phòng '.$r->room_number)->join(', ')],
                        ['📅', 'Check-in',      \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y')],
                        ['📅', 'Trả phòng',     \Carbon\Carbon::now()->format('d/m/Y') . ' (hôm nay)'],
                        ['🌙', 'Số đêm',        $nights . ' đêm'],
                    ] as [$icon, $label, $val])
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:14px">
                            <span style="color:#888">{{ $icon }} {{ $label }}</span>
                            <span style="font-weight:600">{{ $val }}</span>
                        </div>
                    @endforeach

                    {{-- Tổng kết thanh toán --}}
                    <div style="margin-top:12px;background:#fff8f8;border-radius:8px;padding:12px;">
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
                            <span style="color:#666">Tổng tiền phòng</span>
                            <span>{{ number_format($booking->total_price, 0, ',', '.') }} ₫</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
                            <span style="color:#27ae60">Đã đặt cọc</span>
                            <span style="color:#27ae60">- {{ number_format($booking->deposit_amount ?? 0, 0, ',', '.') }} ₫</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:15px;font-weight:700;border-top:1px solid #eee;padding-top:8px;margin-top:4px">
                            <span style="color:#dc3545">Còn lại</span>
                            <span style="color:#dc3545">{{ number_format($remaining, 0, ',', '.') }} ₫</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hướng dẫn --}}
            <div class="card shadow-sm" style="border-radius:14px;">
                <div class="card-body">
                    <div style="font-size:13px;font-weight:700;color:#666;margin-bottom:12px;text-transform:uppercase">Hướng dẫn thanh toán</div>
                    @foreach([
                        ['🏦', 'Mở app ngân hàng',  'Chọn quét QR / chuyển khoản'],
                        ['📷', 'Quét mã QR',         'Hướng camera vào mã QR bên trái'],
                        ['✅', 'Nhập đúng số tiền',  'Kiểm tra số tiền còn lại và xác nhận'],
                        ['🔄', 'Tự động xác nhận',   'Hệ thống tự động cập nhật sau khi nhận tiền'],
                    ] as [$icon, $title, $desc])
                        <div style="display:flex;gap:12px;margin-bottom:10px;align-items:flex-start">
                            <div style="width:30px;height:30px;background:#e3f2fd;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">{{ $icon }}</div>
                            <div>
                                <div style="font-weight:600;font-size:13px">{{ $title }}</div>
                                <div style="color:#888;font-size:12px">{{ $desc }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-warning text-center">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        Booking sẽ được hoàn tất và phòng chuyển sang dọn dẹp sau khi nhận được thanh toán. Quá trình xác nhận có thể mất 1–2 phút.
    </div>
</div>
</main>
@endsection

@push('scripts')
<script>
    const BOOKING_ID = {{ $booking->id }};
    const CHECK_URL  = '{{ route('staff.bookings.check-status', $booking->id) }}';

    // ── Countdown 1 phút ──────────────────────────────────
    const timerKey = 'vietqr_checkout_timer_' + BOOKING_ID;
    if (!localStorage.getItem(timerKey)) {
        localStorage.setItem(timerKey, Date.now().toString());
    }
    let time = Math.max(0, 300 - Math.floor((Date.now() - parseInt(localStorage.getItem(timerKey))) / 1000));

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
                '<div class="alert alert-danger mt-2">⚠️ Mã QR đã hết hiệu lực. <button class="btn btn-sm btn-primary ms-2" onclick="location.reload()">Tạo lại mã</button></div>';
            countdownEl.textContent = '0:00';
            return;
        }
        countdownEl.textContent = fmt(time);
    }, 1000);

    // ── Polling mỗi 5 giây ────────────────────────────────
    const pollTimer = setInterval(function () {
        fetch(CHECK_URL, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
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
