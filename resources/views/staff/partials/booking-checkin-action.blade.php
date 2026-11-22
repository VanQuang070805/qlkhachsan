@php
    $today = now('Asia/Ho_Chi_Minh');
    $isArrivalDay = $booking->check_in?->toDateString() === $today->toDateString();
    $roomsReady = $booking->rooms->isNotEmpty()
        && $booking->rooms->every(fn ($room) => $room->status === \App\Models\Room::STATUS_AVAILABLE);
    $paymentReady = $booking->payment_method === 'cash' || $booking->isPaid();
    $canAct = $canCheckIn && $booking->status === 'confirmed' && $isArrivalDay && $roomsReady && $paymentReady && $today->hour < 18;
    $isEarly = $today->hour < 14;
@endphp

@if($canAct)
    <form method="POST" action="{{ $isEarly ? route('staff.bookings.early-checkin', $booking->id) : route('staff.bookings.checkin', $booking->id) }}">
        @csrf
        @method('PATCH')
        <button class="schedule-checkin-button" type="submit">
            <i class="bi {{ $isEarly ? 'bi-sunrise' : 'bi-box-arrow-in-right' }}" aria-hidden="true"></i>
            {{ $isEarly ? 'Check-in sớm' : 'Check-in' }}
        </button>
    </form>
@elseif($booking->status === 'confirmed' && $isArrivalDay && ! $roomsReady)
    <span class="schedule-action-note">Phòng chưa trống</span>
@elseif($booking->status === 'confirmed' && $isArrivalDay && ! $paymentReady)
    <span class="schedule-action-note">Chưa đủ thanh toán</span>
@else
    <span class="schedule-action-note">—</span>
@endif
