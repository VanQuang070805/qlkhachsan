<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IotRoomController extends Controller
{
    public function showCleaningRequest(Request $request, string $roomNumber)
    {
        if ($response = $this->rejectUnauthorizedDevice($request, $roomNumber)) {
            return $response;
        }

        $room = Room::query()->where('room_number', $roomNumber)->firstOrFail();

        return response()->json([
            'success' => true,
            'room_number' => $room->room_number,
            'status' => $room->status,
            'needs_cleaning' => $room->needs_cleaning,
        ]);
    }

    public function updateCleaningRequest(Request $request, string $roomNumber)
    {
        if ($response = $this->rejectUnauthorizedDevice($request, $roomNumber)) {
            return $response;
        }

        $validated = $request->validate([
            'needs_cleaning' => ['required', 'boolean'],
        ]);

        $room = DB::transaction(function () use ($roomNumber, $validated) {
            $room = Room::query()
                ->where('room_number', $roomNumber)
                ->lockForUpdate()
                ->firstOrFail();

            $needsCleaning = (bool) $validated['needs_cleaning'];
            if ($needsCleaning && ! in_array($room->status, [Room::STATUS_OCCUPIED, Room::STATUS_CLEANING], true)) {
                abort(409, 'Chỉ có thể yêu cầu dọn phòng đang lưu trú hoặc đang chờ dọn.');
            }
            $room->update([
                'status' => ! $needsCleaning && $room->status === Room::STATUS_CLEANING
                    ? Room::STATUS_AVAILABLE
                    : $room->status,
                'needs_cleaning' => $needsCleaning,
                'cleaning_requested_at' => $needsCleaning ? now() : null,
            ]);

            return $room->fresh();
        });

        return response()->json([
            'success' => true,
            'room_number' => $room->room_number,
            'status' => $room->status,
            'needs_cleaning' => $room->needs_cleaning,
            'message' => $room->needs_cleaning
                ? "Phòng {$room->room_number} cần dọn dẹp."
                : "Đã tắt yêu cầu dọn dẹp phòng {$room->room_number}.",
        ]);
    }

    private function rejectUnauthorizedDevice(Request $request, string $roomNumber)
    {
        $configuredKey = (string) config('iot.device_api_key');
        $providedKey = (string) $request->header('X-API-Key');

        if ($configuredKey === '' || $providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json(['message' => 'Khóa thiết bị không hợp lệ.'], 401);
        }

        if ($roomNumber !== (string) config('iot.cleaning_room_number', '501')) {
            return response()->json(['message' => 'Thiết bị không được phép điều khiển phòng này.'], 403);
        }

        return null;
    }
}
