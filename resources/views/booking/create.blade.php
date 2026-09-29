@extends('layouts.main')

@section('content')
@php
    $pageTitle = 'Đặt Phòng — Thông Tin Khách Lưu Trú';
    $adults = (int)($adults ?? 1);
    $children = (int)($children ?? 0);
    $nights = (int)($nights ?? 1);
    $firstRoom = $rooms->first();
    $roomTypeName = $firstRoom?->roomType?->type_name ?? 'Grand Ocean Panorama Suite';
    $roomImage = !empty($firstRoom?->roomType?->image) ? asset($firstRoom->roomType->image) : asset('images/rooms/1.jpg');
@endphp

<div class="aeth-canvas">
    <div class="aeth-spatial-6xl aeth-animate-in">

        {{-- ── 1. Capsule Stepper (Ảnh 3 & docs/02_dat_phong_buoc_1.md) ── --}}
        <div class="text-center">
            <div class="aeth-step-capsule">
                <div class="aeth-capsule-item is-active" aria-current="step">
                    <span class="aeth-capsule-num">1</span>
                    <span>Thông tin khách</span>
                </div>
                <span class="aeth-capsule-arrow">→</span>
                <div class="aeth-capsule-item is-inactive">
                    <span class="aeth-capsule-num">2</span>
                    <span>Thanh toán</span>
                </div>
            </div>
        </div>

        @if(session('error') || !empty($error))
        <div class="alert alert-danger rounded-4 border-danger-subtle shadow-xs mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') ?? $error }}
        </div>
        @endif

        {{-- ── 2. Grid 12 cột: Trái (Form 7) - Phải (Tóm tắt 5) ── --}}
        <div class="row g-4 items-start">

            {{-- ══ CỘT TRÁI (FORM 7 COLS) ══ --}}
            <div class="col-lg-7">
                <div class="aeth-form-card">

                    {{-- Header Form --}}
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-shield-lock-fill text-primary" style="font-size: 15px;"></i>
                        <span style="font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--apple-blue);">
                            BẢO MẬT APPLE PAY &amp; TLS 256-BIT
                        </span>
                    </div>
                    <h1 style="font-size: 24px; font-weight: 800; color: #0f172a; letter-spacing: -0.025em; margin-bottom: 24px;">
                        Thông tin người lưu trú
                    </h1>

                    <form method="POST" action="{{ route('booking.store') }}" id="bookingForm" novalidate>
                        @csrf

                        {{-- Hidden inputs --}}
                        @foreach($roomIds as $rid)
                        <input type="hidden" name="room_ids[]" value="{{ (int)$rid }}">
                        @endforeach
                        <input type="hidden" name="check_in" value="{{ $checkIn }}">
                        <input type="hidden" name="check_out" value="{{ $checkOut }}">
                        <input type="hidden" name="adult_count" value="{{ $adults }}">
                        <input type="hidden" name="child_count" value="{{ $children }}">

                        {{-- Field 1: Họ và tên --}}
                        <div class="aeth-field-group">
                            <div class="aeth-field-header">
                                <label class="aeth-label" for="customerName">Họ và tên <span class="text-danger">*</span></label>
                                <span class="aeth-label-hint">Theo hộ chiếu / CCCD</span>
                            </div>
                            <div class="aeth-input-wrap">
                                <i class="bi bi-person aeth-input-icon"></i>
                                <input type="text" name="customer_name" id="customerName" class="aeth-input"
                                       value="{{ old('customer_name', $user->fullname ?? session('user.fullname', '')) }}"
                                       placeholder="Ví dụ: Nguyễn Hoàng Minh" required>
                            </div>
                            @error('customer_name')
                            <small class="text-danger mt-1 d-block" style="font-size: 12px;">{{ $message }}</small>
                            @enderror
                        </div>

                        {{-- Field 2 & 3: Email & Số điện thoại (row 2 cols) --}}
                        <div class="row g-3 aeth-field-group">
                            <div class="col-md-6">
                                <div class="aeth-field-header">
                                    <label class="aeth-label" for="customerEmail">Email nhận xác nhận <span class="text-danger">*</span></label>
                                </div>
                                <div class="aeth-input-wrap">
                                    <i class="bi bi-envelope aeth-input-icon"></i>
                                    <input type="email" name="customer_email" id="customerEmail" class="aeth-input"
                                           value="{{ old('customer_email', $user->email ?? session('user.email', '')) }}"
                                           placeholder="hoangminh.arch@icloud.com" required>
                                </div>
                                @error('customer_email')
                                <small class="text-danger mt-1 d-block" style="font-size: 12px;">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="aeth-field-header">
                                    <label class="aeth-label" for="customerPhone">Số điện thoại <span class="text-danger">*</span></label>
                                </div>
                                <div class="aeth-input-wrap">
                                    <div class="aeth-flag-badge">
                                        <span>🇻🇳</span>
                                        <span>+84</span>
                                    </div>
                                    <input type="tel" name="customer_phone" id="customerPhone" class="aeth-input aeth-input--phone"
                                           value="{{ old('customer_phone', $user->phone ?? session('user.phone', '')) }}"
                                           placeholder="0918849283" maxlength="11" required>
                                </div>
                                @error('customer_phone')
                                <small class="text-danger mt-1 d-block" style="font-size: 12px;">{{ $message }}</small>
                                @enderror
                            </div>
                        </div>

                        {{-- Field 4: Khung giờ nhận phòng (Phân đoạn thời tiết) --}}
                        <div class="aeth-field-group">
                            <div class="aeth-field-header">
                                <label class="aeth-label">Khung giờ nhận phòng</label>
                                <span class="text-emerald-600 d-inline-flex align-items-center gap-1" style="font-size: 11.5px; font-weight: 600;">
                                    <span class="aeth-pulse-dot" style="width:6px; height:6px;"></span>
                                    Check-in chuẩn: 14:00
                                </span>
                            </div>
                            <div class="aeth-segmented-grid">
                                <button type="button" class="aeth-segment-btn is-active" onclick="selectTimeSegment(this, '14:00 - 16:00')">
                                    <i class="bi bi-sun"></i>
                                    <span>14:00 - 16:00</span>
                                </button>
                                <button type="button" class="aeth-segment-btn" onclick="selectTimeSegment(this, '16:00 - 18:00')">
                                    <i class="bi bi-sunset"></i>
                                    <span>16:00 - 18:00</span>
                                </button>
                                <button type="button" class="aeth-segment-btn" onclick="selectTimeSegment(this, 'Sau 18:00')">
                                    <i class="bi bi-moon-stars"></i>
                                    <span>Sau 18:00</span>
                                </button>
                            </div>
                            <input type="hidden" name="arrival_time" id="arrivalTimeInput" value="14:00 - 16:00">
                        </div>

                        {{-- Field 5: Ghi chú cho quản gia --}}
                        <div class="aeth-field-group">
                            <div class="aeth-field-header">
                                <label class="aeth-label" for="conciergeNote">Ghi chú cho quản gia</label>
                                <span class="aeth-label-hint">Không bắt buộc</span>
                            </div>
                            <textarea id="conciergeNote" name="special_requests" rows="2" class="aeth-input" style="height:auto; padding:12px 14px; resize:none;"
                                placeholder="Ưu tiên phòng tầng cao có tầm nhìn hoàng hôn vịnh biển và sắp xếp đón ga đến sân bay theo đúng giờ bay hạ cánh.">{{ old('special_requests') }}</textarea>
                        </div>

                        {{-- Submit Button --}}
                        <div class="mt-4 pt-2">
                            <button type="submit" class="aeth-btn-primary" id="btnSubmitStep1">
                                <span>Tiếp tục đến thanh toán</span>
                                <i class="bi bi-arrow-right"></i>
                            </button>
                            <p class="text-center text-muted mt-2 mb-0" style="font-size: 11px;">
                                Bảo mật giao dịch theo chuẩn Apple Pay &amp; PCI-DSS Level 1.
                            </p>
                        </div>

                    </form>

                </div>
            </div>

            {{-- ══ CỘT PHẢI (TÓM TẮT DÍNH 5 COLS) ══ --}}
            <div class="col-lg-5">
                <div class="aeth-sticky-sidebar aeth-sticky-sidebar--step1">
                    <div class="aeth-booking-box">

                        {{-- Media phòng --}}
                        <div style="position:relative; width:100%; height:180px; border-radius:18px; overflow:hidden; border:1px solid #e2e8f0;">
                            <img src="{{ $roomImage }}" alt="{{ $roomTypeName }}" style="width:100%; height:100%; object-fit:cover;">
                            <span style="position:absolute; top:12px; left:12px; background:rgba(255,255,255,0.92); backdrop-filter:blur(8px); padding:4px 10px; border-radius:999px; font-size:10.5px; font-weight:700; color:var(--apple-blue); box-shadow:0 2px 6px rgba(0,0,0,0.06);">
                                HẠNG THƯỢNG HẠNG
                            </span>
                            <span style="position:absolute; bottom:12px; right:12px; background:rgba(15,23,42,0.8); backdrop-filter:blur(6px); padding:3px 8px; border-radius:6px; font-size:11px; font-weight:600; color:#fff;">
                                145 m²
                            </span>
                        </div>

                        {{-- Tên phòng & Dải thông số --}}
                        <div>
                            <h2 style="font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">{{ $roomTypeName }}</h2>
                            <div class="d-flex flex-wrap align-items-center gap-2 text-slate-500" style="font-size: 12px; font-weight: 500;">
                                <span><i class="bi bi-calendar-event text-primary me-1"></i>{{ date('d', strtotime($checkIn)) }} - {{ date('d Thm', strtotime($checkOut)) }}</span>
                                <span>•</span>
                                <span><i class="bi bi-moon-stars text-primary me-1"></i>{{ $nights }} đêm</span>
                                <span>•</span>
                                <span><i class="bi bi-people text-primary me-1"></i>{{ $adults + $children }} Khách</span>
                            </div>
                        </div>

                        {{-- 3 Đặc quyền độc bản đính kèm --}}
                        <div style="background:#f8fafc; border:1px solid #f1f5f9; border-radius:14px; padding:12px 14px; display:flex; flex-direction:column; gap:6px;">
                            <div class="d-flex align-items-center gap-2" style="font-size:12px; color:#334155;">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Buffet sáng cao cấp tại Terrace Horizon</span>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="font-size:12px; color:#334155;">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>Đưa đón sân bay VIP chuyên cơ/Mercedes</span>
                            </div>
                            <div class="d-flex align-items-center gap-2" style="font-size:12px; color:#334155;">
                                <i class="bi bi-check-circle-fill text-emerald-600"></i>
                                <span>60 phút trị liệu Signature Spa đôi</span>
                            </div>
                        </div>

                        {{-- Bảng tính tiền thu nhỏ --}}
                        <div class="aeth-breakdown">
                            <div class="aeth-breakdown-row">
                                <span>Tiền phòng ({{ $nights }} đêm)</span>
                                <strong>{{ number_format($total, 0, ',', '.') }}đ</strong>
                            </div>
                            <div class="aeth-total-box" style="margin-top:6px;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 600; color: #1e3a8a;">Tổng thanh toán</div>
                                    <small style="font-size: 10.5px; color: #64748b;">Theo giá phòng đã chọn</small>
                                </div>
                                <strong style="font-size: 19px; color: var(--apple-blue);">{{ number_format($total, 0, ',', '.') }}đ</strong>
                            </div>
                        </div>

                        </div>

                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
function civilDayNumber(ymd) {
    if (!ymd) return 0;
    const parts = ymd.split('-').map(Number);
    if (parts.length < 3) return 0;
    return Math.floor(Date.UTC(parts[0], parts[1] - 1, parts[2]) / 86400000);
}

function selectTimeSegment(btn, timeVal) {
    document.querySelectorAll('.aeth-segment-btn').forEach(b => b.classList.remove('is-active'));
    btn.classList.add('is-active');
    document.getElementById('arrivalTimeInput').value = timeVal;
}
</script>
@endsection
