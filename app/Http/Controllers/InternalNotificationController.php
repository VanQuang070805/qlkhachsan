<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InternalNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $now = now('Asia/Ho_Chi_Minh');
        $seenAt = $request->session()->get('internal_notifications_seen_at');
        $seen = $seenAt ? Carbon::parse($seenAt) : $now->copy()->subDay();
        $createdColumn = (new Booking)->getCreatedAtColumn();

        $bookingQuery = Booking::query()
            ->with('rooms:id,room_number')
            ->where($createdColumn, '>=', $now->copy()->subDay());

        $bookingItems = (clone $bookingQuery)
            ->orderByDesc($createdColumn)
            ->limit(6)
            ->get()
            ->map(function (Booking $booking) use ($createdColumn): array {
                $rooms = $booking->rooms->pluck('room_number')->join(', ');
                $createdAt = $booking->getAttribute($createdColumn);

                return [
                    'type' => 'booking',
                    'title' => "Booking mới #{$booking->id}",
                    'message' => trim($booking->customer_name.' · Phòng '.($rooms ?: 'chưa gán')),
                    'time' => optional($createdAt ? Carbon::parse($createdAt) : null)?->toIso8601String(),
                    'url' => route('staff.booking-calendar.show', $booking),
                ];
            });

        $cleaningRooms = Room::query()
            ->where('needs_cleaning', true)
            ->orderByRaw('cleaning_requested_at IS NULL')
            ->orderByDesc('cleaning_requested_at')
            ->limit(6)
            ->get();

        $cleaningItems = $cleaningRooms->map(fn (Room $room): array => [
            'type' => 'cleaning',
            'title' => "Phòng {$room->room_number} cần dọn",
            'message' => $room->status === Room::STATUS_OCCUPIED ? 'Khách đang lưu trú yêu cầu dọn phòng' : 'Phòng chờ hoàn tất dọn dẹp',
            'time' => optional($room->cleaning_requested_at)?->toIso8601String(),
            'url' => route('staff.bookings', ['search' => $room->room_number]),
        ]);

        $items = $bookingItems
            ->concat($cleaningItems)
            ->sortByDesc(fn (array $item) => $item['time'] ?? '')
            ->values()
            ->take(10);

        return response()->json([
            'unread_count' => (clone $bookingQuery)->where($createdColumn, '>', $seen)->count() + $cleaningRooms->count(),
            'items' => $items,
        ])->header('Cache-Control', 'private, no-store');
    }

    public function markRead(Request $request): JsonResponse
    {
        $request->session()->put('internal_notifications_seen_at', now('Asia/Ho_Chi_Minh')->toIso8601String());

        return response()->json(['success' => true]);
    }
}
