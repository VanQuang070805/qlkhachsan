@extends('layouts.main')

@section('title', 'Quét QR Thanh Toán')

@section('content')
@php
    $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out);
    $rooms  = $booking->rooms;
@endphp

<div class="container" style="max-width:1200px;margin:auto;">
    <h1 class="page-title">Quét mã QR để thanh toán</h1>
    <p class="text-muted">Đặt phòng #{{ $booking->id }} · Sử dụng app ngân hàng bất kỳ để quét mã</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px">

        {{-- QR Card --}}
        <div class="card" style="text-align:center">
            <div style="background:linear-gradient(135deg,#1565C0,#42a5f5);padding:20px;color:#fff">
                <div style="font-size:18px;font-weight:700">VietQR - Chuyển khoản</div>
                <div style="font-size:13px;opacity:.85;margin-top:4px">Hỗ trợ 40+ ngân hàng</div>
            </div>
            <div class="card-body">
                @if(!empty($qrData['qr_url']))
                    <img src="{{ $qrData['qr_url'] }}"
                         alt="VietQR Code"
                         style="width:100%;max-width:260px;border-radius:12px;border:3px solid #1565C0">
                @else
                    <div style="width:260px;height:260px;background:#f0f0f0;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto;font-size:60px">
                        🏦
                    </div>
                    <p style="color:#888;margin-top:12px;font-size:13px">Không tạo được mã QR. Vui lòng dùng thông tin chuyển khoản bên cạnh.</p>
                @endif

                <div style="margin-top:16px;background:#e3f2fd;border-radius:8px;padding:12px;text-align:left">
                    <div style="font-size:13px;font-weight:700;color:#1565C0;margin-bottom:8px">📋 Nội dung chuyển khoản:</div>
                    <code style="font-size:15px;font-weight:700;color:#0d47a1">{{ $qrData['reference_code'] ?? 'KS'.$booking->id }}</code>
                </div>

                <div style="background:#fff3cd;border:1px solid #ffeeba;padding:10px;border-radius:8px;margin-top:10px;font-size:14px;text-align:center;">
                    Mã hết hạn sau <strong id="countdown">1:00</strong>
                </div>

                {{-- Trạng thái polling --}}
                <div id="pollStatus" style="margin-top:10px;font-size:13px;color:#888;text-align:center">
                    <span class="spinner-border spinner-border-sm me-1" role="status"></span>
                    Đang chờ xác nhận thanh toán...
                </div>
            </div>
        </div>

        {{-- Info + Steps --}}
        <div style="display:flex;flex-direction:column;gap:16px">
            {{-- Amount --}}
            <div class="card">
                <div class="card-body" style="text-align:center">
                    <div style="color:#666;font-size:15px">Số tiền đặt cọc (50%)</div>
                    <div style="font-size:32px;font-weight:800;color:#1565C0;margin:8px 0">
                        {{ number_format($booking->deposit_amount, 0, ',', '.') }} ₫
                    </div>
                    <div style="font-size:12px;color:#999;margin-bottom:6px">Tổng: {{ number_format($booking->total_price, 0, ',', '.') }} ₫ — Phần còn lại thanh toán khi nhận phòng</div>
                    <div class="badge bg-info text-dark">{{ $nights }} đêm · {{ $booking->adult_count }} khách</div>
                </div>
            </div>

            {{-- Booking Info --}}
            <div class="card">
                <div class="card-body">
                    <div style="font-size:13px;font-weight:700;color:#666;margin-bottom:10px;text-transform:uppercase">Thông tin đặt phòng</div>
                    @foreach([
                        '👤 Khách hàng' => $booking->customer_name,
                        '🏨 Phòng'      => $rooms->map(fn($r) => 'Phòng '.$r->room_number)->join(', '),
                        '📅 Nhận phòng' => \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y'),
                        '📅 Trả phòng'  => \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y'),
                    ] as $label => $val)
                        <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #f0f0f0;font-size:14px">
                            <span style="color:#888">{{ $label }}</span>
                            <span style="font-weight:600">{{ $val }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Steps --}}
            <div class="card">
                <div class="card-body">
                    <div style="font-size:13px;font-weight:700;color:#666;margin-bottom:12px;text-transform:uppercase">Hướng dẫn thanh toán</div>
                    @foreach([
                        ['🏦', 'Mở app ngân hàng', 'Chọn quét QR / chuyển khoản'],
                        ['📷', 'Quét mã QR',        'Hướng camera vào mã QR bên trái'],
                        ['✅', 'Xác nhận',           'Kiểm tra số tiền và xác nhận'],
                        ['📧', 'Nhận xác nhận',      'Email xác nhận sẽ được gửi tự động'],
                    ] as [$icon, $title, $desc])
                        <div style="display:flex;gap:12px;margin-bottom:12px;align-items:flex-start">
                            <div style="width:32px;height:32px;background:#e3f2fd;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0">{{ $icon }}</div>
                            <div>
                                <div style="font-weight:600;font-size:14px">{{ $title }}</div>
                                <div style="color:#888;font-size:12px">{{ $desc }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Actions --}}
    <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap">
        <a href="{{ route('payment.form', $booking->id) }}" class="btn btn-outline-secondary">
            ← Đổi phương thức
        </a>
    </div>

    <div class="alert alert-warning mt-4 text-center">
        Lưu ý: Đặt phòng sẽ được xác nhận tự động sau khi nhận được chuyển khoản. Quá trình có thể mất 5–15 phút. Nếu sau 30 phút chưa nhận được email xác nhận, vui lòng liên hệ hotline.
    </div>
</div>
@endsection

@push('scripts')
<script>
    // ── Countdown 3 phút ──────────────────────────────────
    const timerKey = 'vietqr_timer_{{ $booking->id }}';
    if (!localStorage.getItem(timerKey)) {
        localStorage.setItem(timerKey, Date.now().toString());
    }
    let time = Math.max(0, 60 - Math.floor((Date.now() - parseInt(localStorage.getItem(timerKey))) / 1000));

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
            .catch(() => {}); // bỏ qua lỗi mạng tạm thời
    }, 5000);
</script>
@endpush