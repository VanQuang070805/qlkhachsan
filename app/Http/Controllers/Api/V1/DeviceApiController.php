<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Room;
use Illuminate\Http\Request;

class DeviceApiController extends ApiController
{
    public function showCleaningRequest(Request $request, string $roomNumber)
    {
        if ($resp = $this->authorizeDevice($request)) {
            return $resp;
        }

        $room = Room::where('room_number', $roomNumber)->first();
        if (! $room) {
            return $this->fail('ROOM_NOT_FOUND', 'Không tìm thấy phòng.', 404);
        }

        return $this->ok([
            'room_number' => (string) $room->room_number,
            'needs_cleaning' => (bool) $room->needs_cleaning,
            'cleaning_requested_at' => $room->cleaning_requested_at?->toIso8601String(),
        ]);
    }

    public function storeCleaningRequest(Request $request, string $roomNumber)
    {
        if ($resp = $this->authorizeDevice($request)) {
            return $resp;
        }

        $room = Room::where('room_number', $roomNumber)->first();
        if (! $room) {
            return $this->fail('ROOM_NOT_FOUND', 'Không tìm thấy phòng.', 404);
        }

        $room->update([
            'needs_cleaning' => true,
            'cleaning_requested_at' => now(),
        ]);

        return $this->ok([
            'room_number' => (string) $room->room_number,
            'needs_cleaning' => true,
            'cleaning_requested_at' => $room->cleaning_requested_at?->toIso8601String(),
        ]);
    }

    private function authorizeDevice(Request $request)
    {
        $configuredKey = trim((string) config('services.devices.api_key', ''));
        if ($configuredKey !== '') {
            $provided = (string) ($request->bearerToken() ?: $request->header('X-Device-Api-Key', ''));
            if ($provided === '' || ! hash_equals($configuredKey, $provided)) {
                return $this->fail('DEVICE_UNAUTHORIZED', 'Thiết bị chưa được xác thực.', 401);
            }
        }

        return null;
    }
}
