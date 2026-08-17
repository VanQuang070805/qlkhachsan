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

        $checkIn   = $request->check_in;
        $checkOut  = $request->check_out;
        $adults    = (int) $request->adults;
        $children  = (int) ($request->children ?? 0);
        $guests    = $adults + $children;
        $nights    = Carbon::parse($checkIn)->diffInDays($checkOut);

        // Room_id đã bị đặt trùng ngày
        $bookedRoomIds = \DB::table('booking_rooms')
            ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
            ->where('bookings.payment_status', 'paid')
            ->where('bookings.status', '!=', 'cancelled')
            ->where('bookings.check_in', '<', $checkOut)
            ->where('bookings.check_out', '>', $checkIn)
            ->pluck('booking_rooms.room_id');

        // Lấy loại phòng có phòng trống và đủ sức chứa
        $roomTypes = RoomType::with([
            'amenities',
            'rooms' => function ($q) {
                $q->where('status', 'available');
            }
        ])
            ->where('max_adults', '>=', $adults)
            ->where('max_guests', '>=', $guests)
            ->whereHas('rooms', function ($q) use ($bookedRoomIds) {
                $q->where('status', 'available')
                  ->whereNotIn('id', $bookedRoomIds);
            })
            ->withCount(['rooms as available_count' => function ($q) use ($bookedRoomIds) {
                $q->where('status', 'available')->whereNotIn('id', $bookedRoomIds);
            }])
            ->get();

        foreach ($roomTypes as $roomType) {
            // Tính giá trung bình sau khi điều chỉnh cho khoảng ngày lưu trú
            $totalAdjustedPrice = PriceSetting::calculateTotalPrice((float) $roomType->price, $checkIn, $checkOut);
            $roomType->price = $totalAdjustedPrice / max($nights, 1);

            $roomType->available_rooms = $roomType->rooms->map(function ($r) use ($bookedRoomIds) {
                $r->is_booked = $bookedRoomIds->contains($r->id);
                return $r;
            })->sortBy(['floor', 'room_number']);
        }

        $totalAvailable = $roomTypes->sum('available_count');
        $allAmenities = \App\Models\Amenity::all();

        return view('room.search', compact(
            'roomTypes', 'checkIn', 'checkOut',
            'adults', 'children', 'nights',
            'totalAvailable', 'allAmenities'
        ));
    }

    /**
     * Chi tiết loại phòng
     */
    public function detail(int $id, Request $request)
    {
        $room      = RoomType::with(['amenities', 'reviews.user'])->findOrFail($id);
        $avgRating = $room->averageRating();

        $checkIn  = $request->check_in;
        $checkOut = $request->check_out;

        // Lấy room_id đã bị đặt trong khoảng ngày (nếu có chọn ngày)
        $bookedRoomIds = collect();
        if ($checkIn && $checkOut) {
            $nights = Carbon::parse($checkIn)->diffInDays(Carbon::parse($checkOut));
            $totalAdjustedPrice = PriceSetting::calculateTotalPrice((float) $room->price, $checkIn, $checkOut);
            $room->price = $totalAdjustedPrice / max($nights, 1);

            $bookedRoomIds = \DB::table('booking_rooms')
                ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
                ->where('bookings.payment_status', 'paid')
                ->where('bookings.status', '!=', 'cancelled')
                ->where('bookings.check_in', '<', $checkOut)
                ->where('bookings.check_out', '>', $checkIn)
                ->pluck('booking_rooms.room_id');
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
    public function amenities(?int $id = null)
    {
        if ($id) {
            $roomType = RoomType::with('amenities')->findOrFail($id);
            return response()->json($roomType->amenities);
        }

        $roomTypes = RoomType::with('amenities')->get();
        return view('room.amenities', compact('roomTypes'));
    }
}