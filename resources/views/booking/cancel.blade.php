@extends('layouts.main')

@section('title', 'Hủy Đặt Phòng')

@section('content')
<div style="max-width:680px;margin:auto">
    <h2 class="fw-bold mb-1">Hủy Đặt Phòng</h2>
    <p class="text-muted mb-4">Đặt phòng #{{ $booking->id }} · {{ $booking->customer_name }}</p>

    {{-- Thông tin booking --}}
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-6">
                    <div class="text-muted small">Phòng</div>
                    <div class="fw-semibold">
                        {{ $booking->rooms->map(fn($r) => 'Phòng '.$r->room_number)->join(', ') }}
                    </div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Tổng tiền</div>
                    <div class="fw-semibold text-primary">{{ number_format($booking->total_price, 0, ',', '.') }} ₫</div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Nhận phòng</div>
                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($booking->check_in)->format('d/m/Y') }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted small">Trả phòng</div>
                    <div class="fw-semibold">{{ \Carbon\Carbon::parse($booking->check_out)->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chính sách hoàn tiền --}}
    @if($isEligible)
        <div class="alert alert-success d-flex gap-3 mb-4">
            <i class="bi bi-check-circle-fill fs-4 mt-1"></i>
            <div>
                <div class="fw-bold">Đủ điều kiện hoàn tiền</div>
                <div class="small">
                    Bạn còn <strong>{{ $daysLeft }} ngày</strong> trong thời hạn hủy miễn phí ({{ $deadlineDays }} ngày trước nhận phòng).
                    Số tiền <strong>{{ number_format($booking->total_price, 0, ',', '.') }} ₫</strong> sẽ được hoàn trong 3–5 ngày làm việc.
                </div>
            </div>
        </div>
    @else
        <div class="alert alert-warning d-flex gap-3 mb-4">
            <i class="bi bi-exclamation-triangle-fill fs-4 mt-1"></i>
            <div>
                <div class="fw-bold">Không đủ điều kiện hoàn tiền</div>
                <div class="small">
                    Đã quá thời hạn hủy miễn phí ({{ $deadlineDays }} ngày trước nhận phòng).
                    Hủy lúc này sẽ <strong>không được hoàn tiền</strong>.
                    @if($isWeekendOrHoliday)
                        <br>Lưu ý: Ngày nhận phòng là cuối tuần hoặc ngày lễ, chính sách hủy có thể khác.
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Form hủy --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('booking.cancel', $booking->id) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">Lý do hủy <span class="text-danger">*</span></label>
                    <textarea name="reason" class="form-control @error('reason') is-invalid @enderror"
                        rows="3" placeholder="Vui lòng cho biết lý do bạn muốn hủy đặt phòng..."
                        required maxlength="500">{{ old('reason') }}</textarea>
                    @error('reason')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="{{ route('booking.mine') }}" class="btn btn-outline-secondary">
                        ← Quay lại
                    </a>
                    <button type="submit" class="btn btn-danger"
                        onclick="return confirm('Bạn chắc chắn muốn hủy đặt phòng này?')">
                        <i class="bi bi-x-circle me-1"></i>Xác nhận hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection