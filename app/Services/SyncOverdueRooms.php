<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;

class SyncOverdueRooms
{
    /**
     * Đồng bộ trạng thái phòng sang 'overdue' (Quá giờ trả) khi khách đang lưu trú
     * quá giờ trả phòng dự kiến:
     * - Chuyển ngay tại thời điểm đến hạn trả phòng nếu phòng có lịch đặt kế tiếp trong ngày.
     * - Hoặc chuyển sau 30 phút kể từ giờ trả phòng dự kiến nếu chưa được gia hạn.
     */
    public function sync(?Carbon $now = null): int
    {
        $now = $now ? $now->copy()->setTimezone('Asia/Ho_Chi_Minh') : now('Asia/Ho_Chi_Minh');

        $bookings = Booking::query()
            ->with(['rooms', 'stayExtensionLogs'])
            ->where('status', 'checked_in')
            ->whereDate('check_out', '<=', $now->toDateString())
            ->get();

        if ($bookings->isEmpty()) {
            return 0;
        }

        $roomIdsToMarkOverdue = [];

        foreach ($bookings as $booking) {
            $occupiedRooms = $booking->rooms->filter(
                fn (Room $room) => $room->status === Room::STATUS_OCCUPIED
            );

            if ($occupiedRooms->isEmpty()) {
                continue;
            }

            $dueAt = $booking->scheduledCheckoutAt();
            if ($now->lt($dueAt)) {
                continue;
            }

            if ($now->gte($dueAt->copy()->addMinutes(30))) {
                foreach ($occupiedRooms as $room) {
                    $roomIdsToMarkOverdue[] = $room->id;
                }

                continue;
            }

            $checkoutDate = $booking->check_out->toDateString();
            $nextDate = Carbon::parse($checkoutDate, 'Asia/Ho_Chi_Minh')->addDay()->toDateString();
            $upcomingReservedRoomIds = Booking::reservedRoomIds($checkoutDate, $nextDate, $booking->id);

            if ($upcomingReservedRoomIds->isEmpty()) {
                continue;
            }

            foreach ($occupiedRooms as $room) {
                if ($upcomingReservedRoomIds->contains($room->id)) {
                    $roomIdsToMarkOverdue[] = $room->id;
                }
            }
        }

        $roomIdsToMarkOverdue = array_values(array_unique($roomIdsToMarkOverdue));
        if ($roomIdsToMarkOverdue === []) {
            return 0;
        }

        return Room::query()
            ->whereIn('id', $roomIdsToMarkOverdue)
            ->where('status', Room::STATUS_OCCUPIED)
            ->update(['status' => Room::STATUS_OVERDUE]);
    }
}
