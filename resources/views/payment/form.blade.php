@extends('layouts.main')

@section('title', 'Chọn phương thức thanh toán')

@section('content')
@php
    $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out);
@endphp

<div class="container" style="max-width:1200px;margin:auto;">
    <h1 class="page-title mt-2">Chọn phương thức thanh toán</h1>
    <p class="text-muted">Đặt phòng #{{ $booking->id }} đã được tạo. Hoàn tất thanh toán để xác nhận.</p>

    <div style="display:grid;grid-template-columns:40% 60%;gap:24px;justify-items:center;margin-top:20px;">

        {{-- Booking Summary --}}
        <div class="card mb-2" style="width:100%;max-width:480px;">
            <div class="card-header" style="background:#1A1A2E;">
                <h2 class="mb-0" style="color:#fff;text-align:center;font-size:1.2rem;">Tóm tắt đặt phòng</h2>
            </div>
            <div class="card-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    @foreach([
                        '👤 Tên khách'  => $booking->customer_name,
                        '📞 SĐT'        => $booking->customer_phone,
                        '📧 Email'      => $booking->customer_email,
                        '🏨 Phòng'      => $booking->rooms->map(fn($r) => 'Phòng '.$r->room_number)->join(', '),
                        '📅 Nhận phòng' => \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y'),
                        '📅 Trả phòng'  => \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y'),
                        '🌙 Số đêm'     => $nights.' đêm',
                        '👥 Số khách'   => $booking->adult_count.' người lớn, '.$booking->child_count.' trẻ em',
                    ] as $label => $value)
                        <div style="background:#FAF8F3;border-radius:8px;padding:12px">
                            <div style="font-size:12px;color:#888;margin-bottom:3px">{{ $label }}</div>
                            <div style="font-size:15px">{{ $value }}</div>
                        </div>
                    @endforeach
                </div>
                <div style="background:#1A1A2E;border-radius:10px;padding:20px;margin-top:16px;text-align:center;color:#fff">
                    <div style="font-size:13px;opacity:.85;margin-bottom:4px">SỐ TIỀN ĐẶT CỌC (50%)</div>
                    <div style="font-size:36px;font-weight:800">{{ number_format($booking->deposit_amount, 0, ',', '.') }} ₫</div>
                    <div style="font-size:12px;opacity:.7;margin-top:4px">Tổng giá phòng: {{ number_format($booking->total_price, 0, ',', '.') }} ₫ — Thanh toán phần còn lại khi nhận phòng</div>
                </div>
            </div>
        </div>

        {{-- Payment Methods --}}
        <div class="card" style="width:100%;">
            <div class="card-header" style="background:#1A1A2E;">
                <h2 class="mb-0" style="color:#fff;font-size:1.2rem;">Phương thức thanh toán</h2>
            </div>
            <div class="card-body">
                <form action="{{ route('payment.update', $booking->id) }}" method="POST" id="paymentForm">
                    @csrf
                    @method('PATCH')

                    @foreach([
                        ['momo', '#ae2070', 'Ví MoMo',               'Thanh toán qua ứng dụng MoMo · Nhanh chóng & an toàn', 'PHỔ BIẾN', '#fce4ec', '#ae2070'],
                        ['vietqr', '#1565C0', 'Chuyển khoản VietQR',   'Quét mã QR · Hỗ trợ tất cả ngân hàng Việt Nam',         null,       null,      null],
                        ['zalopay', '#0068ff', 'ZaloPay',               'Thanh toán qua ví ZaloPay',                              null,       null,      null],
                        ['vnpay', '#e53935', 'VNPay',                 'Thanh toán qua cổng VNPay · Hỗ trợ nhiều ngân hàng',     null,       null,      null],
                    ] as [$val, $color, $name, $desc, $badge, $badgeBg, $badgeColor])
                        <label class="payment-option" for="method_{{ $val }}"
                               style="display:flex;align-items:center;gap:16px;padding:18px;border:2px solid #eee;border-radius:12px;cursor:pointer;margin-bottom:12px;transition:all .2s">
                            <input type="radio" name="payment_method" id="method_{{ $val }}" value="{{ $val }}"
                                   onchange="selectMethod(this)"
                                   style="width:20px;height:20px;accent-color:{{ $color }}">
                            <div>
                                <div style="font-weight:700;font-size:16px">{{ $name }}</div>
                                <div style="color:#888;font-size:13px;margin-top:2px">{{ $desc }}</div>
                            </div>
                            @if($badge)
                                <div style="margin-left:auto;background:{{ $badgeBg }};color:{{ $badgeColor }};padding:4px 12px;border-radius:20px;font-size:12px;font-weight:600">{{ $badge }}</div>
                            @endif
                        </label>
                    @endforeach

                    <div id="methodError" class="alert alert-warning" style="display:none">
                        ⚠️ Vui lòng chọn phương thức thanh toán.
                    </div>

                    <div class="text-center">
                        <button type="button" onclick="submitPayment()"
                                class="btn btn-lg" id="payBtn" disabled
                                style="width:320px;padding:14px 0;background:linear-gradient(135deg,#C9A84C,#9A7335);color:#fff;border:none;border-radius:8px">
                            Thanh toán ngay
                        </button>
                    </div>
                    <p class="text-center text-muted small mt-3">Thông tin thanh toán được mã hóa SSL an toàn</p>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function selectMethod(radio) {
        document.querySelectorAll('.payment-option').forEach(el => {
            el.style.borderColor = '#eee';
            el.style.background  = '#fff';
        });
        radio.closest('label').style.borderColor = '#212529';
        radio.closest('label').style.background  = '#f8f9fa';
        document.getElementById('payBtn').disabled = false;
        document.getElementById('methodError').style.display = 'none';
        const names = { momo: 'Thanh toán MoMo', vietqr: 'Tạo mã QR', zalopay: 'Thanh toán ZaloPay', vnpay: 'Thanh toán VNPay' };
        document.getElementById('payBtn').textContent = names[radio.value] || 'Thanh toán ngay';
    }

    function submitPayment() {
        const selected = document.querySelector('input[name="payment_method"]:checked');
        if (!selected) {
            document.getElementById('methodError').style.display = 'flex';
            return;
        }
        const btn = document.getElementById('payBtn');
        btn.disabled = true;
        btn.textContent = 'Đang xử lý...';
        document.getElementById('paymentForm').submit();
    }
</script>
@endpush