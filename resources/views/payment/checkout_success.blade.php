@extends('layouts.dashboard')

@section('content')
@php
    $nights = \Carbon\Carbon::parse($booking->check_in)->diffInDays($booking->check_out);
@endphp
<main class="main-content">
<div style="max-width:700px;margin:auto;">

    <div class="card shadow-sm" style="border-radius:16px;overflow:hidden;text-align:center;">
        <div style="background:linear-gradient(135deg,#28a745,#48d468);padding:36px 24px;color:#fff;">
            <div style="font-size:56px;margin-bottom:12px;">✅</div>
            <h2 style="font-size:1.8rem;font-weight:800;margin:0 0 6px;">Trả phòng thành công!</h2>
            <p style="opacity:.9;margin:0;font-size:1rem;">Booking #{{ $booking->id }} · Thanh toán đã xác nhận</p>
        </div>

        <div class="card-body p-4">

            {{-- Thông báo thanh toán --}}
            <div class="alert alert-success d-flex align-items-center gap-3 text-start mb-4" style="border-radius:10px;">
                <i class="fa-solid fa-circle-check fa-2x text-success flex-shrink-0"></i>
                <div>
                    <div class="fw-bold">Thanh toán đã được ghi nhận</div>
                    <div class="text-muted" style="font-size:0.875rem;">
                        Tổng tiền <strong>{{ number_format($booking->total_price, 0, ',', '.') }} ₫</strong> đã thanh toán đầy đủ.
                        Phòng đang chuyển sang trạng thái dọn dẹp.
                    </div>
                </div>
            </div>

            {{-- Chi tiết --}}
            <div style="background:#f8f9fa;border-radius:12px;padding:20px;text-align:left;margin-bottom:20px;">
                <h6 class="fw-bold text-uppercase text-muted mb-3" style="font-size:.8rem;letter-spacing:.05em;">Chi tiết trả phòng</h6>

                @foreach([
                    ['👤 Khách hàng',   $booking->customer_name],
                    ['🏨 Phòng',         $booking->rooms->map(fn($r) => 'Phòng '.$r->room_number)->join(', ')],
                    ['📅 Check-in',      \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y')],
                    ['📅 Check-out',     \Carbon\Carbon::parse($booking->actual_check_out)->format('d/m/Y H:i')],
                    ['🌙 Số đêm',        $nights . ' đêm'],
                    ['💳 Phương thức',   match($booking->payment_method) {
                        'vietqr'  => 'Chuyển khoản VietQR',
                        'cash'    => 'Tiền mặt',
                        'momo'    => 'Ví MoMo',
                        'zalopay' => 'ZaloPay',
                        'vnpay'   => 'VNPay',
                        default   => $booking->payment_method
                    }],
                ] as [$label, $val])
                    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #e9ecef;font-size:.9rem;">
                        <span style="color:#6c757d;">{{ $label }}</span>
                        <span style="font-weight:600;">{{ $val }}</span>
                    </div>
                @endforeach

                {{-- Tổng kết thanh toán --}}
                <div style="margin-top:12px;background:#fff;border-radius:8px;padding:14px;border:1px solid #e9ecef;">
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:4px;">
                        <span style="color:#6c757d;">Tổng tiền phòng</span>
                        <span>{{ number_format($booking->total_price, 0, ',', '.') }} ₫</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:4px;">
                        <span style="color:#28a745;">Đặt cọc trước</span>
                        <span style="color:#28a745;">{{ number_format($booking->deposit_amount ?? 0, 0, ',', '.') }} ₫</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:8px;">
                        <span style="color:#28a745;">Thanh toán khi trả phòng</span>
                        <span style="color:#28a745;">{{ number_format($booking->total_price - ($booking->deposit_amount ?? 0), 0, ',', '.') }} ₫</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:1rem;font-weight:800;border-top:2px solid #28a745;padding-top:8px;margin-top:4px;">
                        <span class="text-success">Đã thanh toán đủ</span>
                        <span class="text-success">{{ number_format($booking->total_price, 0, ',', '.') }} ₫</span>
                    </div>
                </div>
            </div>

            {{-- Trạng thái phòng --}}
            <div class="alert alert-info d-flex align-items-center gap-2 mb-4" style="border-radius:10px;font-size:.875rem;">
                <i class="fa-solid fa-broom text-info"></i>
                <span>Phòng {{ $booking->rooms->map(fn($r) => $r->room_number)->join(', ') }} đã chuyển sang trạng thái <strong>đang dọn dẹp</strong>.</span>
            </div>

            <a href="{{ route('staff.bookings') }}" class="btn btn-primary w-100" style="border-radius:10px;padding:12px;font-weight:700;font-size:1rem;">
                <i class="fa-solid fa-arrow-left me-2"></i> Quay về sơ đồ phòng
            </a>
        </div>
    </div>

</div>
</main>
@endsection
