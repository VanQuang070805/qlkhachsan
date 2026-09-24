@extends('layouts.main')

@section('content')
<?php $pageTitle = 'Xác Nhận Hủy Phòng'; ?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('booking.mine') }}">Đặt Phòng Của Tôi</a></li>
        <li class="breadcrumb-item active">Hủy Phòng</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-7">

        {{-- Tiêu đề --}}
        <div class="text-center mb-4">
            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:3.5rem"></i>
            <h2 class="fw-bold mt-2">Xác Nhận Hủy Đặt Phòng</h2>
            <p class="text-muted">Vui lòng đọc kỹ chính sách hoàn tiền trước khi xác nhận hủy.</p>
        </div>

        {{-- Thông tin booking --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Thông Tin Đặt Phòng #{{ $booking->id }}</h5>
            </div>
            <div class="card-body p-4">
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted w-40">Khách hàng</td>
                        <td class="fw-semibold">{{ $booking->customer_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Phòng</td>
                        <td class="fw-semibold">
                            @foreach ($booking->rooms as $room)
                                <span>Phòng {{ $room->room_number }}
                                    @if ($room->roomType)
                                        <span class="text-muted small">({{ $room->roomType->name }})</span>
                                    @endif
                                </span>@if (!$loop->last), @endif
                            @endforeach
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Nhận phòng</td>
                        <td>{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Trả phòng</td>
                        <td>{{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tổng tiền</td>
                        <td class="fw-bold text-primary fs-5">
                            {{ number_format($booking->total_price, 0, ',', '.') }} VNĐ
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Trạng thái</td>
                        <td>
                            @php
                                $badgeMap = [
                                    'pending'   => ['label' => 'Chờ xác nhận', 'class' => 'warning text-dark'],
                                    'confirmed' => ['label' => 'Đã xác nhận',  'class' => 'success'],
                                    'cancelled' => ['label' => 'Đã hủy',        'class' => 'secondary'],
                                    'completed' => ['label' => 'Hoàn tất',      'class' => 'info'],
                                ];
                                $badge = $badgeMap[$booking->status] ?? ['label' => $booking->status, 'class' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Chính sách hoàn tiền --}}
        @if ($isEligible)
            <div class="alert alert-success d-flex gap-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-4 mt-1 flex-shrink-0"></i>
                <div>
                    <h6 class="fw-bold mb-1">Đủ điều kiện hoàn tiền</h6>
                    <p class="mb-0">
                        Bạn hủy trước <strong>{{ $deadlineDays }} ngày</strong> so với ngày nhận phòng
                        @if ($daysLeft !== null)
                            (còn <strong>{{ $daysLeft }} ngày</strong> để hủy miễn phí).
                        @else
                            .
                        @endif
                        Sau khi hủy, bộ phận lễ tân sẽ xử lý hoàn tiền
                        <strong>{{ number_format($booking->total_price, 0, ',', '.') }} VNĐ</strong>
                        trong vòng <strong>3–5 ngày làm việc</strong>.
                    </p>
                    @if ($isWeekendOrHoliday)
                        <p class="mb-0 mt-1 text-warning-emphasis">
                            <i class="bi bi-info-circle me-1"></i>
                            Lưu ý: Ngày nhận phòng rơi vào cuối tuần / ngày lễ, thời gian xử lý hoàn tiền có thể kéo dài hơn.
                        </p>
                    @endif
                </div>
            </div>
        @else
            <div class="alert alert-danger d-flex gap-3 mb-4" role="alert">
                <i class="bi bi-x-circle-fill fs-4 mt-1 flex-shrink-0"></i>
                <div>
                    <h6 class="fw-bold mb-1">Không đủ điều kiện hoàn tiền</h6>
                    <p class="mb-0">
                        Theo chính sách, bạn phải hủy trước <strong>{{ $deadlineDays }} ngày</strong>
                        để được hoàn tiền. Thời hạn đó đã qua — nếu tiếp tục, bạn sẽ
                        <strong>mất toàn bộ {{ number_format($booking->total_price, 0, ',', '.') }} VNĐ</strong>.
                    </p>
                    @if ($isWeekendOrHoliday)
                        <p class="mb-0 mt-1">
                            <i class="bi bi-calendar-event me-1"></i>
                            Ngày nhận phòng là cuối tuần / ngày lễ — chính sách hoàn tiền được áp dụng chặt hơn.
                        </p>
                    @endif
                </div>
            </div>
        @endif

        {{-- Form lý do hủy --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <form action="{{ route('booking.cancel', $booking->id) }}" method="POST"
                      onsubmit="return confirm('Bạn chắc chắn muốn hủy đặt phòng này?')">
                    @csrf

                    <div class="mb-3">
                        <label for="reason" class="form-label fw-semibold">
                            Lý do hủy <span class="text-danger">*</span>
                        </label>
                        <textarea
                            id="reason"
                            name="reason"
                            class="form-control @error('reason') is-invalid @enderror"
                            rows="4"
                            maxlength="500"
                            placeholder="Vui lòng cho chúng tôi biết lý do bạn hủy đặt phòng..."
                            required
                        >{{ old('reason') }}</textarea>
                        @error('reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text text-end">
                            <span id="charCount">0</span>/500 ký tự
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-end flex-wrap">
                        <a href="{{ route('booking.mine') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>Quay Lại
                        </a>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-x-circle me-1"></i>Xác Nhận Hủy Phòng
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script>
    const textarea = document.getElementById('reason');
    const counter  = document.getElementById('charCount');
    textarea.addEventListener('input', () => { counter.textContent = textarea.value.length; });
</script>
@endsection