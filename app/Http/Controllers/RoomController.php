<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\PriceSetting;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class RoomController extends Controller
{
    /**
     * Danh sách loại phòng + form tìm kiếm
     */
    public function index(Request $request)
    {
        $now = now('Asia/Ho_Chi_Minh');
        $earliestCheckIn = $now->copy()->startOfDay();
        if ($now->hour >= 17) {
            $earliestCheckIn->addDay();
        }

        $searchSubmitted = $request->boolean('search');
        $filters = $searchSubmitted ? $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'check_in' => ['required', 'date_format:Y-m-d'],
            'check_out' => ['required', 'date_format:Y-m-d', 'after:check_in'],
            'adults' => ['required', 'integer', 'min:0', 'max:20'],
            'children' => ['required', 'integer', 'min:0', 'max:20'],
        ]) : [];

        $checkIn = $filters['check_in'] ?? $earliestCheckIn->toDateString();
        $checkOut = $filters['check_out'] ?? $earliestCheckIn->copy()->addDay()->toDateString();
        if ($searchSubmitted && $checkIn < $earliestCheckIn->toDateString()) {
            throw ValidationException::withMessages([
                'check_in' => $now->hour >= 17
                    ? 'Sau 17:00, vui lòng chọn ngày nhận phòng từ ngày mai.'
                    : 'Ngày nhận phòng không thể ở trong quá khứ.',
            ]);
        }

        $adults = (int) ($filters['adults'] ?? 1);
        $children = (int) ($filters['children'] ?? 0);
        $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
        $reservedRoomIds = Booking::reservedRoomIds($checkIn, $checkOut)
            ->map(fn ($id) => (int) $id)
            ->flip();

        $roomTypes = RoomType::with(['amenities', 'rooms'])
            ->when(filled($filters['q'] ?? null), function ($query) use ($filters) {
                $term = trim($filters['q']);
                $query->where(function ($match) use ($term) {
                    $match->where('type_name', 'like', "%{$term}%")
                        ->orWhere('description', 'like', "%{$term}%")
                        ->orWhereHas('amenities', fn ($amenities) => $amenities->where('amenity_name', 'like', "%{$term}%"));
                });
            })
            ->get()
            ->map(function (RoomType $type) use ($reservedRoomIds, $checkIn, $checkOut, $nights) {
                $type->available_count = $type->rooms->filter(fn (Room $room) =>
                    $room->status === Room::STATUS_AVAILABLE && !$reservedRoomIds->has((int) $room->id)
                )->count();
                $type->price = PriceSetting::calculateTotalPrice((float) $type->price, $checkIn, $checkOut) / max($nights, 1);
                return $type;
            });

        if ($searchSubmitted) {
            $guestCount = $adults + $children;
            $roomTypes = $roomTypes->filter(function (RoomType $type) use ($adults, $children, $guestCount) {
                $adultCapacity = (int) $type->max_adults;
                $childCapacity = (int) $type->max_children;
                $guestCapacity = (int) $type->max_guests;

                if ($adultCapacity < 1 || $guestCapacity < 1 || ($children > 0 && $childCapacity < 1)) {
                    return false;
                }

                $roomsNeeded = max(
                    (int) ceil($adults / $adultCapacity),
                    $children > 0 ? (int) ceil($children / $childCapacity) : 0,
                    (int) ceil($guestCount / $guestCapacity),
                );

                return $type->available_count >= $roomsNeeded;
            })->values();
        }

        $filters = [
            'q' => $filters['q'] ?? '',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => $adults,
            'children' => $children,
        ];

        return view('room.index', [
            'rooms' => $roomTypes,
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
            'check_in'  => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'adults'    => 'required|integer|min:1',
            'children'  => 'nullable|integer|min:0',
        ]);

        $earliestCheckIn = now('Asia/Ho_Chi_Minh')->hour >= 17 ? now('Asia/Ho_Chi_Minh')->addDay()->toDateString() : now('Asia/Ho_Chi_Minh')->toDateString();
        if ($request->check_in < $earliestCheckIn) {
            return back()->withInput()->withErrors(['check_in' => 'Sau 17:00, vui lòng chọn ngày nhận phòng từ ngày mai.']);
        }

        $checkIn   = $request->check_in;
        $checkOut  = $request->check_out;
        $adults    = (int) $request->adults;
        $children  = (int) ($request->children ?? 0);
        $guests    = $adults + $children;
        $nights    = Carbon::parse($checkIn)->diffInDays($checkOut);

        // Room_id đã bị đặt trùng ngày
        $bookedRoomIds = \App\Models\Booking::reservedRoomIds($checkIn, $checkOut);

        // Giữ cả hạng phòng hiện hết phòng để khách thấy các số phòng bị vô hiệu hóa.
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
            // Tính giá trung bình sau khi điều chỉnh cho khoảng ngày lưu trú
            $totalAdjustedPrice = PriceSetting::calculateTotalPrice((float) $roomType->price, $checkIn, $checkOut);
            $roomType->price = $totalAdjustedPrice / max($nights, 1);

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

        $earliestCheckIn = now('Asia/Ho_Chi_Minh')->hour >= 17 ? now('Asia/Ho_Chi_Minh')->addDay()->toDateString() : now('Asia/Ho_Chi_Minh')->toDateString();
        if ($request->filled('check_in') && $request->check_in < $earliestCheckIn) {
            throw \Illuminate\Validation\ValidationException::withMessages(['check_in' => 'Sau 17:00, vui lòng chọn ngày nhận phòng từ ngày mai.']);
        }

        $room      = RoomType::with(['amenities', 'reviews.user'])->findOrFail($id);
        $avgRating = $room->averageRating();

        $checkIn  = $request->check_in ?: $earliestCheckIn;
        $checkOut = $request->check_out ?: Carbon::parse($checkIn)->addDay()->toDateString();

        // Lấy room_id đã bị đặt trong khoảng ngày (nếu có chọn ngày)
        $bookedRoomIds = collect();
        if ($checkIn && $checkOut) {
            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
            $totalAdjustedPrice = PriceSetting::calculateTotalPrice((float) $room->price, $checkIn, $checkOut);
            $room->price = $totalAdjustedPrice / max($nights, 1);

            $bookedRoomIds = \App\Models\Booking::reservedRoomIds($checkIn, $checkOut);
        }
    
        // Lấy tất cả phòng của loại này
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
                'available_ids' => $allRoomsOfType->filter(fn ($item) => !$item->is_booked && $item->status === 'available')->pluck('id')->values(),
                'rooms' => $allRoomsOfType->map(fn ($item) => [
                    'id' => $item->id,
                    'available' => !$item->is_booked && $item->status === 'available',
                    'status' => $item->is_booked ? 'reserved' : $item->status,
                ])->values(),
                'nightly_price' => (float) $room->price,
            ])->header('Cache-Control', 'no-store');
        }

        $adults   = (int) ($request->adults ?? 1);
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
