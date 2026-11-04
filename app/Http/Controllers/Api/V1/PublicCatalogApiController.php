<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Amenity;
use App\Models\PriceSetting;
use App\Models\RoomType;
use App\Services\CacheVersionService;
use App\Services\Chatbot\HotelRoomToolService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class PublicCatalogApiController extends ApiController
{
    public function __construct(
        private readonly HotelRoomToolService $roomTools,
        private readonly CacheVersionService $versions,
    ) {}

    public function roomTypes()
    {
        $result = $this->roomTools->getPublicRoomTypes();

        return $this->ok($result['room_types']);
    }

    public function showRoomType(RoomType $roomType)
    {
        $roomType->loadMissing('amenities:id,name,icon');

        return $this->ok([
            'id' => (int) $roomType->id,
            'type_name' => (string) $roomType->type_name,
            'base_price_vnd' => (int) round((float) $roomType->price),
            'max_adults' => (int) $roomType->max_adults,
            'max_children' => (int) $roomType->max_children,
            'max_guests' => (int) $roomType->max_guests,
            'description' => (string) ($roomType->description ?? ''),
            'amenities' => $roomType->amenities->map(fn (Amenity $a) => [
                'id' => (int) $a->id,
                'name' => (string) $a->name,
                'icon' => (string) ($a->icon ?? ''),
            ])->values()->all(),
        ]);
    }

    public function amenities()
    {
        $catalogVersion = $this->versions->catalogVersion();
        $data = Cache::remember("royal:catalog:amenities:v{$catalogVersion}", now()->addMinutes(5), function () {
            return Amenity::query()
                ->orderBy('name')
                ->get(['id', 'name', 'icon'])
                ->map(fn (Amenity $a) => [
                    'id' => (int) $a->id,
                    'name' => (string) $a->name,
                    'icon' => (string) ($a->icon ?? ''),
                ])
                ->values()
                ->all();
        });

        return $this->ok($data);
    }

    public function availability(Request $request)
    {
        $result = $this->roomTools->searchPublicRooms($request->only([
            'check_in',
            'check_out',
            'guests',
            'rooms',
            'adults',
            'children',
            'room_count',
        ]));

        if (isset($result['error'])) {
            return $this->fail('INVALID_AVAILABILITY_QUERY', (string) $result['error'], 422);
        }

        return $this->ok($result);
    }

    public function quote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_type_id' => ['required', 'integer', 'exists:room_types,id'],
            'check_in' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'rooms' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'guests' => ['sometimes', 'integer', 'min:1', 'max:20'],
        ]);

        if ($validator->fails()) {
            return $this->fail('VALIDATION_ERROR', 'Dữ liệu báo giá không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $data = $validator->validated();
        $roomType = RoomType::findOrFail((int) $data['room_type_id']);
        $roomCount = (int) ($data['rooms'] ?? 1);
        $guests = (int) ($data['guests'] ?? 1);

        if ((int) ceil($guests / $roomCount) > (int) $roomType->max_guests) {
            return $this->fail('CAPACITY_EXCEEDED', 'Số lượng khách vượt quá sức chứa tối đa của hạng phòng.', 422);
        }

        $nights = max(1, Carbon::parse($data['check_in'])->diffInDays(Carbon::parse($data['check_out'])));
        $perRoomVnd = (int) round((float) PriceSetting::calculateTotalPrice((float) $roomType->price, $data['check_in'], $data['check_out']));
        $totalVnd = $perRoomVnd * $roomCount;
        $depositVnd = (int) round($totalVnd / 2);

        return $this->ok([
            'room_type_id' => (int) $roomType->id,
            'type_name' => (string) $roomType->type_name,
            'check_in' => $data['check_in'],
            'check_out' => $data['check_out'],
            'nights' => $nights,
            'rooms' => $roomCount,
            'guests' => $guests,
            'base_price_per_night_vnd' => (int) round((float) $roomType->price),
            'total_price_vnd' => $totalVnd,
            'deposit_amount_vnd' => $depositVnd,
        ]);
    }
}
