@extends('layouts.dashboard')

@section('title', 'Booking #'.$booking->id.' · Rosaliza Hotel')

@section('content')
@php
    $statusLabels = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'checked_in' => 'Đang lưu trú',
        'completed' => 'Đã trả phòng',
        'cancelled' => 'Đã hủy',
    ];
    $roomIds = $booking->rooms->pluck('id')->all();
    $scheduleEditable = $canEdit && ! $booking->isPaid();
@endphp

<main class="booking-detail-page">
    <header class="booking-detail-head">
        <div>
            <a href="{{ route('staff.booking-calendar') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i>Lịch đặt phòng</a>
            <h1>Booking #{{ $booking->id }}</h1>
            <p>{{ $booking->customer_name }} · {{ $booking->rooms->pluck('room_number')->join(', ') ?: 'Chưa gán phòng' }}</p>
        </div>
        <div class="booking-detail-actions">
            <span class="schedule-status is-{{ $booking->status }}">{{ $statusLabels[$booking->status] ?? $booking->status }}</span>
            @include('staff.partials.booking-checkin-action', ['booking' => $booking, 'canCheckIn' => $canCheckIn])
        </div>
    </header>

    <section class="booking-detail-summary" aria-label="Tóm tắt booking">
        <div><span>Nhận phòng</span><strong>{{ $booking->check_in?->format('d/m/Y') }}</strong><small>14:00, có thể check-in sớm khi phòng trống</small></div>
        <div><span>Trả phòng</span><strong>{{ $booking->check_out?->format('d/m/Y') }}</strong><small>{{ $booking->scheduledCheckoutAt()->format('H:i') }}</small></div>
        <div><span>Khách</span><strong>{{ $booking->adult_count }} người lớn</strong><small>{{ $booking->child_count }} trẻ em</small></div>
        <div><span>Tổng tiền</span><strong>{{ number_format((float) $booking->total_price, 0, ',', '.') }} đ</strong><small>{{ $booking->payment_status === 'paid' ? 'Đã thanh toán' : 'Chưa thanh toán đủ' }}</small></div>
    </section>

    <div class="booking-detail-grid">
        <section class="booking-detail-panel">
            <header><div><h2>Thông tin đặt phòng</h2><p>{{ $canEdit ? 'Cập nhật thông tin khách và lịch lưu trú.' : 'Booking này chỉ có thể xem.' }}</p></div><i class="bi bi-journal-text" aria-hidden="true"></i></header>
            <form class="booking-edit-form" method="POST" action="{{ route('staff.booking-calendar.update', $booking) }}">
                @csrf
                @method('PATCH')
                <label for="booking-customer-name"><span>Họ tên khách</span><input id="booking-customer-name" name="customer_name" value="{{ old('customer_name', $booking->customer_name) }}" required @disabled(!$canEdit)></label>
                <label for="booking-customer-phone"><span>Số điện thoại</span><input id="booking-customer-phone" name="customer_phone" value="{{ old('customer_phone', $booking->customer_phone) }}" inputmode="tel" required @disabled(!$canEdit)></label>
                <label class="booking-field-wide" for="booking-customer-email"><span>Email</span><input id="booking-customer-email" name="customer_email" type="email" value="{{ old('customer_email', $booking->customer_email) }}" required @disabled(!$canEdit)></label>

                @if($scheduleEditable)
                    <label for="booking-check-in"><span>Ngày nhận</span><input id="booking-check-in" name="check_in" type="date" value="{{ old('check_in', $booking->check_in?->toDateString()) }}" required></label>
                    <label for="booking-check-out"><span>Ngày trả</span><input id="booking-check-out" name="check_out" type="date" value="{{ old('check_out', $booking->check_out?->toDateString()) }}" required></label>
                    <label for="booking-adults"><span>Người lớn</span><input id="booking-adults" name="adult_count" type="number" min="1" value="{{ old('adult_count', $booking->adult_count) }}" required></label>
                    <label for="booking-children"><span>Trẻ em</span><input id="booking-children" name="child_count" type="number" min="0" value="{{ old('child_count', $booking->child_count) }}" required></label>
                    <fieldset class="booking-room-selector booking-field-wide">
                        <legend>Phòng</legend>
                        <div>
                            @foreach($allRooms as $room)
                                @php($selected = in_array($room->id, old('room_ids', $roomIds)))
                                <label class="booking-room-option {{ $room->status !== \App\Models\Room::STATUS_AVAILABLE && !$selected ? 'is-unavailable' : '' }}">
                                    <input type="checkbox" name="room_ids[]" value="{{ $room->id }}" @checked($selected) @disabled($room->status !== \App\Models\Room::STATUS_AVAILABLE && !$selected)>
                                    <span><strong>{{ $room->room_number }}</strong><small>{{ $room->roomType?->type_name }} · {{ $room->status === \App\Models\Room::STATUS_AVAILABLE ? 'Trống' : 'Không sẵn sàng' }}</small></span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                @elseif($booking->isPaid() && $canEdit)
                    <p class="booking-edit-note booking-field-wide"><i class="bi bi-lock" aria-hidden="true"></i>Booking đã thanh toán: chỉ sửa được tên và thông tin liên hệ.</p>
                @endif

                @if($errors->any())
                    <div class="booking-form-errors booking-field-wide" role="alert">{{ $errors->first() }}</div>
                @endif

                @if($canEdit)
                    <div class="booking-form-actions booking-field-wide"><button type="submit"><i class="bi bi-floppy" aria-hidden="true"></i>Lưu thay đổi</button></div>
                @endif
            </form>
        </section>

        <aside class="booking-detail-side">
            <section class="booking-detail-panel">
                <header><div><h2>Phòng đã gán</h2><p>{{ $booking->rooms->count() }} phòng</p></div><i class="bi bi-door-open" aria-hidden="true"></i></header>
                <div class="booking-assigned-rooms">
                    @forelse($booking->rooms as $room)
                        <div><span><strong>Phòng {{ $room->room_number }}</strong><small>{{ $room->roomType?->type_name }}</small></span><span class="schedule-room {{ $room->status === \App\Models\Room::STATUS_AVAILABLE ? 'is-ready' : '' }}">{{ $room->status === \App\Models\Room::STATUS_AVAILABLE ? 'Trống' : $room->status }}</span></div>
                    @empty
                        <p>Booking chưa được gán phòng.</p>
                    @endforelse
                </div>
            </section>

            @if($canEdit)
                <section class="booking-delete-panel">
                    <div><i class="bi bi-trash3" aria-hidden="true"></i><span><strong>Xóa booking</strong><small>Dữ liệu không bị xóa cứng; booking chuyển sang trạng thái đã hủy.</small></span></div>
                    <form method="POST" action="{{ route('staff.booking-calendar.destroy', $booking) }}" data-confirm="Hủy booking #{{ $booking->id }}? Lịch sử và thông tin hoàn tiền vẫn được giữ.">
                        @csrf
                        @method('DELETE')
                        <label for="booking-cancel-reason">Lý do</label>
                        <textarea id="booking-cancel-reason" name="reason" rows="3" maxlength="500" placeholder="Lý do hủy booking"></textarea>
                        <button type="submit"><i class="bi bi-trash3" aria-hidden="true"></i>Xóa booking</button>
                    </form>
                </section>
            @endif
        </aside>
    </div>
</main>
@endsection
