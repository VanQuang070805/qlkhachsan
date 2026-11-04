<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Review;

class HomeController extends Controller
{
    public function index()
    {
        $featuredRooms = RoomType::with('amenities')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->withCount(['rooms as available_count' => fn($q) => $q->where('status', 'available')])
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
            ->get();

        $latestReviews = Review::with(['user', 'roomType'])
            ->where('rating', '>=', 4)
            ->latest()
            ->take(6)
            ->get();
        $roomTypes = RoomType::all();

        return view('home.index', compact('featuredRooms', 'latestReviews', 'roomTypes'));
    }

    public function contact() { return view('home.contact'); }
}
