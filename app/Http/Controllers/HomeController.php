<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Review;
use App\Models\Booking;
use App\Models\PriceSetting;
use App\Models\Room;

class HomeController extends Controller
{
    public function index()
    {
        $checkIn = now('Asia/Ho_Chi_Minh');
        if ($checkIn->hour >= 16) {
            $checkIn->addDay();
        }
        $checkIn = $checkIn->toDateString();
        $checkOut = \Carbon\Carbon::parse($checkIn)->addDay()->toDateString();
        $reservedRoomIds = Booking::reservedRoomIds($checkIn, $checkOut)->all();

        $featuredRooms = RoomType::with('amenities')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->withCount(['rooms as available_count' => fn($q) => $q
                ->where('status', Room::STATUS_AVAILABLE)
                ->whereNotIn('rooms.id', $reservedRoomIds)])
            ->selectSub(function ($query) {
                $query->from('booking_rooms')
                    ->join('rooms', 'rooms.id', '=', 'booking_rooms.room_id')
                    ->join('bookings', 'bookings.id', '=', 'booking_rooms.booking_id')
                    ->whereColumn('rooms.room_type_id', 'room_types.id')
                    ->whereIn('bookings.status', ['confirmed', 'checked_in', 'completed'])
                    ->selectRaw('count(distinct bookings.id)');
            }, 'booking_count')
            ->orderByDesc('booking_count')
            ->orderBy('id')
            ->take(4)
            ->get()
            ->each(function (RoomType $type) use ($checkIn, $checkOut) {
                $type->display_price = PriceSetting::calculateTotalPrice((float) $type->price, $checkIn, $checkOut);
            });

        $latestReviews = Review::with(['user', 'roomType'])
            ->where('rating', '>=', 4)
            ->latest()
            ->take(6)
            ->get();
        $roomTypes = RoomType::all();

        return view('home.index', compact('featuredRooms', 'latestReviews', 'roomTypes', 'checkIn', 'checkOut'));
    }

    public function contact() { return view('home.contact'); }
}
