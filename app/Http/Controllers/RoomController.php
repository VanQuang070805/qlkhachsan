<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\PriceSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Support\StayDates;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    /**
     * Danh sách loại phòng + form tìm kiếm
     */
    public function index(Request $request)
    {
        $now = now('Asia/Ho_Chi_Minh');
        $earliestCheckIn = $now->copy()->startOfDay();
        if ($now->hour >= 18) {
            $earliestCheckIn->addDay();
        }

        $searchSubmitted = $request->boolean('search');
        $filters = $searchSubmitted ? $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:1', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
        ]) : [];

        $checkIn = $filters['check_in'] ?? $earliestCheckIn->toDateString();
        $checkOut = $filters['check_out'] ?? $earliestCheckIn->copy()->addDay()->toDateString();
        if ($searchSubmitted && $checkIn < $earliestCheckIn->toDateString()) {
            throw ValidationException::withMessages([
                'check_in' => $now->hour >= 18
                    ? 'Từ 18:00, vui lòng chọn ngày nhận phòng từ ngày mai.'
                    : 'Ngày nhận phòng không thể ở trong quá khứ.',
            ]);
        }
        StayDates::validate($checkIn, $checkOut);

        $adults = (int) ($filters['adults'] ?? 1);
        $children = (int) ($filters['children'] ?? 0);
        $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
        $reservedRoomIds = Booking::reservedRoomIds($checkIn, $checkOut)
            ->map(fn ($id) => (int) $id)
            ->flip();

        $roomTypes = RoomType::with(['amenities', 'rooms'])
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $terms = array_values(array_filter(array_map('trim', explode(',', $filters['q']))));
                $query->where(function ($match) use ($terms) {
                    foreach ($terms as $index => $term) {
                        $clause = fn ($item) => $item
                            ->where('type_name', 'like', "%{$term}%")
                            ->orWhere('description', 'like', "%{$term}%")
                            ->orWhereHas('amenities', fn ($amenities) => $amenities->where('amenity_name', 'like', "%{$term}%"));

                        $index === 0 ? $match->where($clause) : $match->orWhere($clause);
                    }
                });
            })
            ->get()
            ->map(function (RoomType $type) use ($reservedRoomIds, $checkIn, $checkOut, $nights) {
                $type->available_count = $type->rooms->filter(fn (Room $room) =>
                    $room->status === Room::STATUS_AVAILABLE && ! $reservedRoomIds->has((int) $room->id)
                )->count();
                $type->price = round(PriceSetting::calculateTotalPrice((float) $type->price, $checkIn, $checkOut) / max($nights, 1));

                return $type;
            });

        if ($searchSubmitted) {
            $guestCount = $adults + $children;
            $roomTypes = $roomTypes->filter(fn (RoomType $type) =>
                (int) $type->max_adults >= $adults
                && (int) $type->max_children >= $children
                && (int) $type->max_guests >= $guestCount
                && $type->available_count > 0
            )->values();
        }

        $filters = [
            'q' => $filters['q'] ?? '',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => $adults,
            'children' => $children,
        ];
        $roomTypeOptions = RoomType::query()->orderBy('type_name')->pluck('type_name');

        return view('room.index', [
            'rooms' => $roomTypes,
            'roomTypeOptions' => $roomTypeOptions,
            'filters' => $filters,
            'earliestCheckIn' => $earliestCheckIn->toDateString(),
            'searchSubmitted' => $searchSubmitted,
        ]);
    }

    /**
     * Tìm phòng theo ngày, số khách
     */
    public function search(Request $request)
    {
        $request->validate([
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults' => 'required|integer|min:1',
            'children' => 'nullable|integer|min:0',
        ]);

        $earliestCheckIn = now('Asia/Ho_Chi_Minh')->hour >= 18 ? now('Asia/Ho_Chi_Minh')->addDay()->toDateString() : now('Asia/Ho_Chi_Minh')->toDateString();
        if ($request->check_in < $earliestCheckIn) {
            return back()->withInput()->withErrors(['check_in' => 'Từ 18:00, vui lòng chọn ngày nhận phòng từ ngày mai.']);
        }

        $checkIn = $request->check_in;
        $checkOut = $request->check_out;
        StayDates::validate($checkIn, $checkOut);
        $adults = (int) $request->adults;
        $children = (int) ($request->children ?? 0);
        $guests = $adults + $children;
        $nights = Carbon::parse($checkIn)->diffInDays($checkOut);

        $bookedRoomIds = Booking::reservedRoomIds($checkIn, $checkOut);

        $roomTypes = RoomType::with(['amenities', 'rooms'])
            ->whereHas('rooms')
            ->withCount(['rooms as inventory_count'])
            ->get()
            ->filter(fn ($type) =>
                $type->inventory_count * (int) $type->max_adults >= $adults
                && $type->inventory_count * (int) $type->max_children >= $children
                && $type->inventory_count * (int) $type->max_guests >= $guests
            )->values();

        foreach ($roomTypes as $roomType) {
            $totalAdjustedPrice = PriceSetting::calculateTotalPrice((float) $roomType->price, $checkIn, $checkOut);
            $roomType->price = round($totalAdjustedPrice / max($nights, 1));

            $roomType->available_rooms = $roomType->rooms->map(function ($r) use ($bookedRoomIds) {
                $r->is_booked = $bookedRoomIds->contains($r->id);

                return $r;
            })->sortBy(['floor', 'room_number']);
        }

        $allAmenities = \App\Models\Amenity::all();

        return view('room.search', compact(
            'roomTypes', 'checkIn', 'checkOut',
            'adults', 'children', 'nights',
            'allAmenities'
        ));
    }

    /**
     * Chi tiết loại phòng
     */
    public function detail(int $id, Request $request)
    {
        $request->validate([
            'check_in' => 'nullable|required_with:check_out|date|after_or_equal:today',
            'check_out' => 'nullable|required_with:check_in|date|after:check_in',
            'adults' => 'nullable|integer|min:1|max:20',
            'children' => 'nullable|integer|min:0|max:20',
        ]);

        $earliestCheckIn = now('Asia/Ho_Chi_Minh')->hour >= 18 ? now('Asia/Ho_Chi_Minh')->addDay()->toDateString() : now('Asia/Ho_Chi_Minh')->toDateString();
        if ($request->filled('check_in') && $request->check_in < $earliestCheckIn) {
            throw ValidationException::withMessages(['check_in' => 'Từ 18:00, vui lòng chọn ngày nhận phòng từ ngày mai.']);
        }

        $room = RoomType::with(['amenities', 'reviews.user'])->findOrFail($id);
        $avgRating = $room->averageRating();

        $checkIn = $request->check_in ?: $earliestCheckIn;
        $checkOut = $request->check_out ?: Carbon::parse($checkIn)->addDay()->toDateString();
        StayDates::validate($checkIn, $checkOut);

        $bookedRoomIds = collect();
        $totalAdjustedPrice = (float) $room->price;
        if ($checkIn && $checkOut) {
            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
            $totalAdjustedPrice = PriceSetting::calculateTotalPrice((float) $room->price, $checkIn, $checkOut);
            $room->price = round($totalAdjustedPrice / max($nights, 1));

            $bookedRoomIds = Booking::reservedRoomIds($checkIn, $checkOut);
        }

        $allRoomsOfType = Room::where('room_type_id', $id)
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get()
            ->map(function ($r) use ($bookedRoomIds) {
                $r->is_booked = $bookedRoomIds->contains($r->id);

                return $r;
            });

        if ($request->expectsJson()) {
            return response()->json([
                'available_ids' => $allRoomsOfType->filter(fn ($item) => ! $item->is_booked && $item->status === 'available')->pluck('id')->values(),
                'rooms' => $allRoomsOfType->map(fn ($item) => [
                    'id' => $item->id,
                    'available' => ! $item->is_booked && $item->status === 'available',
                    'status' => $item->is_booked ? 'reserved' : $item->status,
                ])->values(),
                'nightly_price' => (int) round((float) $room->price),
                'stay_total_per_room' => (int) round($totalAdjustedPrice),
            ])->header('Cache-Control', 'no-store');
        }

        $adults = (int) ($request->adults ?? 1);
        $children = (int) ($request->children ?? 0);

        return view('room.detail', compact(
            'room', 'avgRating',
            'allRoomsOfType', 'bookedRoomIds',
            'checkIn', 'checkOut',
            'adults', 'children'
        ));
    }

    /**
     * Danh sách tiện nghi của loại phòng (AJAX)
     */
    public function amenities(int $id)
    {
        $roomType = RoomType::with('amenities')->findOrFail($id);

        return response()->json($roomType->amenities);
    }
}
