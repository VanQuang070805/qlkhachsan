<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use App\Models\Review;

class HomeController extends Controller
{
    public function index()
    {
        $featuredRooms = RoomType::with('amenities')
            ->withCount(['rooms as available_count' => fn($q) => $q->where('status', 'available')])
            ->orderByDesc('price')
            ->take(3)
            ->get();

        $latestReviews = Review::with(['user', 'roomType'])
            ->where('rating', '>=', 4)
            ->latest()
            ->take(6)
            ->get();
        $roomTypes = RoomType::all();

        return view('home.index', compact('featuredRooms', 'latestReviews', 'roomTypes'));
    }

    public function about()  { return view('home.about'); }
    public function contact() { return view('home.contact'); }
}