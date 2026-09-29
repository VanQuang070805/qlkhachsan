<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\PriceSetting;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomController extends Controller
{
    /**
     * Danh sách loại phòng + form tìm kiếm
     */
    public function index()
    {
        $roomTypes = RoomType::with(['amenities', 'rooms'])
            ->withCount(['rooms as available_count' => function ($q) {
                $q->where('status', 'available');
            }])
            ->get();

        return view('room.index', ['rooms' => $roomTypes]);
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
