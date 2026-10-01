<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\Chatbot\DifyChatbotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChatbotController extends Controller
{
    public function __construct(private readonly DifyChatbotService $dify) {}

    public function api(Request $request)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);
        $answer = $this->dify->ask($request, trim($validated['message']));

        if (! $answer) {
            return response()->json([
                'message' => 'Royal Concierge đang tạm thời mất kết nối. Bạn vui lòng thử lại sau ít phút.',
            ], 503);
        }

        return response()->json(['reply' => $answer['reply']]);
    }

    public function stream(Request $request)
    {
        $response = $this->api($request);
        if ($response->getStatusCode() !== 200) {
            return $response;
        }

        $reply = (string) $response->getData(true)['reply'];

        return response()->stream(function () use ($reply) {
            foreach (preg_split('/(?<=\s)/u', $reply, -1, PREG_SPLIT_NO_EMPTY) as $chunk) {
                echo 'data: '.json_encode(['delta' => $chunk], JSON_UNESCAPED_UNICODE)."\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
            echo 'data: '.json_encode(['done' => true], JSON_UNESCAPED_UNICODE)."\n\n";
            if (ob_get_level() > 0) ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function difyRoomTypes(Request $request)
    {
        if ($response = $this->authorizeDifyTool($request)) {
            return $response;
        }

        return response()->json(['room_types' => RoomType::query()
            ->orderBy('price')
            ->get(['type_name', 'price', 'max_guests'])
            ->map(fn (RoomType $roomType) => [
                'type_name' => $roomType->type_name,
                'price_per_night' => (float) $roomType->price,
                'max_guests' => (int) $roomType->max_guests,
            ])
            ->values()]);
    }

    public function difySearchRooms(Request $request)
    {
        if ($response = $this->authorizeDifyTool($request)) {
            return $response;
        }

        $result = $this->searchRooms($request->only(['check_in', 'check_out', 'guests', 'rooms']));
        if (isset($result['error'])) {
            return response()->json($result, 422);
        }

        return response()->json([
            'check_in' => $result['check_in'],
            'check_out' => $result['check_out'],
            'requested_rooms' => $result['requested_rooms'],
            'room_types' => collect($result['rooms'])->groupBy('room_type')->map(fn ($rooms, $roomType) => [
                'type_name' => $roomType,
                'available_rooms' => $rooms->count(),
                'enough_for_request' => $rooms->count() >= $result['requested_rooms'],
                'price_per_night' => $rooms->min('price_per_night'),
                'max_guests' => $rooms->max('max_guests'),
            ])->values(),
        ]);
    }

    private function authorizeDifyTool(Request $request)
    {
        if (! config('services.dify.live_tools_enabled')) {
            return response()->json(['message' => 'Dify tools are not enabled.'], 503);
        }

        $apiKey = (string) config('services.dify.tool_api_key');
        if ($apiKey === '') {
            return response()->json(['message' => 'Dify tools are not configured.'], 503);
        }

        if (! hash_equals($apiKey, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        return null;
    }

    private function searchRooms(array $arguments): array
    {
        $validator = Validator::make($arguments, [
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'rooms' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);
        if ($validator->fails()) {
            return ['error' => 'Ngày hoặc số khách chưa hợp lệ.'];
        }

        $data = $validator->validated();
        $data['rooms'] = (int) ($data['rooms'] ?? 1);
        if ($data['check_in'] === now()->toDateString() && now()->hour >= 17) {
            return ['error' => 'Sau 17:00 không thể nhận phòng trong ngày hôm nay.'];
        }

        $reserved = Booking::reservedRoomIds($data['check_in'], $data['check_out']);
        $rooms = Room::query()->with('roomType:id,type_name,price,max_guests')
            ->where('status', 'available')->whereNotIn('id', $reserved)
            ->whereHas('roomType', fn ($query) => $query->where('max_guests', '>=', (int) ceil($data['guests'] / $data['rooms'])))
            ->orderBy('room_type_id')->get(['id', 'room_number', 'room_type_id'])
            ->map(fn (Room $room) => [
                'room_type' => $room->roomType?->type_name,
                'price_per_night' => (float) ($room->roomType?->price ?? 0),
                'max_guests' => $room->roomType?->max_guests,
            ])->values()->all();

        return [
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'requested_rooms' => $data['rooms'],
            'rooms' => $rooms,
        ];
    }
}
