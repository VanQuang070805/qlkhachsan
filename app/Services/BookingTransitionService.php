<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use DomainException;
use Illuminate\Support\Facades\DB;

class BookingTransitionService
{
    public function checkIn(int $bookingId): Booking
    {
        return DB::transaction(function () use ($bookingId) {
            $booking = Booking::with('rooms')->lockForUpdate()->findOrFail($bookingId);
            if ($booking->status !== 'confirmed') throw new DomainException('Booking phải ở trạng thái đã xác nhận.');
            if ($booking->check_in->toDateString() !== now('Asia/Ho_Chi_Minh')->toDateString()) {
                throw new DomainException('Booking chưa đến ngày nhận phòng.');
            }
            if ($booking->payment_method !== 'cash' && !$booking->isPaid()) {
                throw new DomainException('Booking trực tuyến chưa thanh toán tiền cọc.');
            }

            $roomIds = $booking->rooms->pluck('id');
            $rooms = Room::whereIn('id', $roomIds)->orderBy('id')->lockForUpdate()->get();
            if ($rooms->count() !== $roomIds->count() || $rooms->contains(fn ($room) => $room->status !== Room::STATUS_AVAILABLE)) {
                throw new DomainException('Một hoặc nhiều phòng chưa sẵn sàng để nhận khách.');
            }
            if (Booking::reservedRoomIds($booking->check_in->toDateString(), $booking->check_out->toDateString(), $booking->id)
                ->intersect($roomIds)->isNotEmpty()) {
                throw new DomainException('Một hoặc nhiều phòng đang có booking khác.');
            }

            $booking->update(['status' => 'checked_in', 'actual_check_in' => now()]);
            Room::whereIn('id', $roomIds)->update(['status' => Room::STATUS_OCCUPIED]);
            return $booking->fresh('rooms');
        });
    }
}
