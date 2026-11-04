<?php

namespace App\Services\Chatbot;

use App\Models\Booking;
use App\Models\PriceSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Services\CacheVersionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class HotelRoomToolService
{
    public function __construct(
        private readonly CacheVersionService $versions = new CacheVersionService(),
    ) {}

    public function getPublicRoomTypes(): array
    {
        $catalogVersion = $this->versions->catalogVersion();
        $cacheKey = "royal:catalog:room-types:v{$catalogVersion}";

        $roomTypes = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return RoomType::query()
                ->orderBy('price')
                ->get(['id', 'type_name', 'price', 'max_adults', 'max_children', 'max_guests', 'description'])
                ->map(fn (RoomType $roomType) => [
                    'id' => (int) $roomType->id,
                    'type_name' => $roomType->type_name,
                    'price_per_night' => (int) round((float) $roomType->price),
                    'base_price_vnd' => (int) round((float) $roomType->price),
                    'max_adults' => (int) $roomType->max_adults,
                    'max_children' => (int) $roomType->max_children,
                    'max_guests' => (int) $roomType->max_guests,
                    'description' => (string) ($roomType->description ?? ''),
                ])
                ->values()
                ->all();
        });

        return [
            'room_types' => $roomTypes,
            'catalog_version' => $catalogVersion,
            'queried_at' => now('Asia/Ho_Chi_Minh')->toIso8601String(),
        ];
    }

    public function searchPublicRooms(array $arguments): array
    {
        $adults = isset($arguments['adults']) ? (int) $arguments['adults'] : null;
        $children = isset($arguments['children']) ? (int) $arguments['children'] : 0;
        $totalGuests = isset($arguments['guests'])
            ? (int) $arguments['guests']
            : max(1, ($adults ?? 1) + $children);
        $roomCount = (int) ($arguments['rooms'] ?? $arguments['room_count'] ?? 1);

        $normalized = [
            'check_in' => $arguments['check_in'] ?? null,
            'check_out' => $arguments['check_out'] ?? null,
            'guests' => $totalGuests,
            'rooms' => $roomCount,
        ];

        $validator = Validator::make($normalized, [
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'guests' => ['required', 'integer', 'min:1', 'max:20'],
            'rooms' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        if ($validator->fails()) {
            return ['error' => 'Ngày hoặc số khách chưa hợp lệ.'];
        }

        $data = $validator->validated();

        if ($data['check_in'] === now('Asia/Ho_Chi_Minh')->toDateString() && now('Asia/Ho_Chi_Minh')->hour >= 16) {
            return ['error' => 'Từ 16:00 không thể nhận phòng trong ngày hôm nay.'];
        }

        $availabilityVersion = $this->versions->availabilityVersion();
        $catalogVersion = $this->versions->catalogVersion();
        // Include timestamp bucket (2s) so time travel in tests or hold expiry never serves stale counts
        $timeBucket = (int) floor(now()->timestamp / 2);
        $criteriaHash = sha1(json_encode([
            $data['check_in'],
            $data['check_out'],
            $data['guests'],
            $data['rooms'],
            $availabilityVersion,
            $catalogVersion,
            $timeBucket,
        ]));

        return Cache::remember("royal:availability:search:{$criteriaHash}", now()->addSeconds(2), function () use ($data) {
            $nights = max(1, Carbon::parse($data['check_in'])->diffInDays(Carbon::parse($data['check_out'])));
            $reserved = Booking::reservedRoomIds($data['check_in'], $data['check_out']);

            $rooms = Room::query()
                ->with('roomType:id,type_name,price,max_adults,max_children,max_guests')
                ->where('status', 'available')
                ->whereNotIn('id', $reserved)
                ->whereHas('roomType', fn ($query) => $query->where('max_guests', '>=', (int) ceil($data['guests'] / $data['rooms'])))
                ->orderBy('room_type_id')
                ->get(['id', 'room_type_id'])
                ->map(function (Room $room) use ($data) {
                    $basePrice = (int) round((float) ($room->roomType?->price ?? 0));
                    $totalPerRoom = (int) round((float) PriceSetting::calculateTotalPrice($basePrice, $data['check_in'], $data['check_out']));

                    return [
                        'room_type_id' => (int) $room->room_type_id,
                        'room_type' => $room->roomType?->type_name,
                        'price_per_night' => $basePrice,
                        'base_price_vnd' => $basePrice,
                        'estimated_total_per_room_vnd' => $totalPerRoom,
                        'max_guests' => (int) ($room->roomType?->max_guests ?? 0),
                    ];
                })
                ->values();

            $grouped = $rooms->groupBy('room_type')->map(fn ($group, $roomType) => [
                'room_type_id' => (int) $group->first()['room_type_id'],
                'type_name' => $roomType,
                'available_rooms' => $group->count(),
                'enough_for_request' => $group->count() >= $data['rooms'],
                'price_per_night' => (int) $group->min('price_per_night'),
                'base_price_vnd' => (int) $group->min('base_price_vnd'),
                'estimated_total_per_room_vnd' => (int) $group->min('estimated_total_per_room_vnd'),
                'max_guests' => (int) $group->max('max_guests'),
            ])->values()->all();

            return [
                'check_in' => $data['check_in'],
                'check_out' => $data['check_out'],
                'nights' => $nights,
                'requested_rooms' => $data['rooms'],
                'queried_at' => now('Asia/Ho_Chi_Minh')->toIso8601String(),
                'room_types' => $grouped,
                'available_room_types' => $grouped,
            ];
        });
    }

    /**
     * Read-only tool returning ONLY the authenticated customer's own bookings.
     * Identity is strictly resolved from the server-side capability token.
     */
    public function getCustomerBookings(int $customerId): array
    {
        if ($customerId <= 0) {
            return [
                'bookings' => [],
                'queried_at' => now('Asia/Ho_Chi_Minh')->toIso8601String(),
            ];
        }

        $bookings = Booking::query()
            ->with('rooms.roomType:id,type_name')
            ->where('user_id', $customerId)
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(function (Booking $booking) {
                $roomTypeNames = $booking->rooms
                    ->map(fn (Room $room) => $room->roomType?->type_name)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'booking_id' => (int) $booking->id,
                    'check_in' => $booking->check_in?->toDateString(),
                    'check_out' => $booking->check_out?->toDateString(),
                    'status' => (string) $booking->status,
                    'payment_status' => (string) $booking->payment_status,
                    'payment_method' => (string) ($booking->payment_method ?? ''),
                    'room_count' => $booking->rooms->count(),
                    'room_types' => $roomTypeNames,
                    'total_price_vnd' => (int) round((float) $booking->total_price),
                    'deposit_amount_vnd' => (int) round((float) $booking->deposit_amount),
                ];
            })
            ->values()
            ->all();

        return [
            'bookings' => $bookings,
            'queried_at' => now('Asia/Ho_Chi_Minh')->toIso8601String(),
        ];
    }
}
