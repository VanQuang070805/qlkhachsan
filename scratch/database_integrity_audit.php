<?php

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$report = [
    'counts' => [
        'users' => DB::table('users')->count(),
        'rooms' => DB::table('rooms')->count(),
        'room_types' => DB::table('room_types')->count(),
        'bookings' => DB::table('bookings')->count(),
        'reviews' => DB::table('reviews')->count(),
        'price_settings' => DB::table('price_settings')->count(),
        'payment_logs' => DB::table('payment_logs')->count(),
    ],
    'orphans' => [
        'booking_user' => DB::table('bookings as b')->leftJoin('users as u', 'u.id', '=', 'b.user_id')->whereNotNull('b.user_id')->whereNull('u.id')->count(),
        'booking_rooms_booking' => DB::table('booking_rooms as br')->leftJoin('bookings as b', 'b.id', '=', 'br.booking_id')->whereNull('b.id')->count(),
        'booking_rooms_room' => DB::table('booking_rooms as br')->leftJoin('rooms as r', 'r.id', '=', 'br.room_id')->whereNull('r.id')->count(),
        'rooms_type' => DB::table('rooms as r')->leftJoin('room_types as rt', 'rt.id', '=', 'r.room_type_id')->whereNull('rt.id')->count(),
        'reviews_user' => DB::table('reviews as rv')->leftJoin('users as u', 'u.id', '=', 'rv.user_id')->whereNull('u.id')->count(),
        'reviews_type' => DB::table('reviews as rv')->leftJoin('room_types as rt', 'rt.id', '=', 'rv.room_type_id')->whereNull('rt.id')->count(),
    ],
    'invalid' => [
        'booking_dates' => DB::table('bookings')->whereColumn('check_out', '<=', 'check_in')->count(),
        'booking_guests' => DB::table('bookings')->where('adult_count', '<', 1)->orWhere('child_count', '<', 0)->count(),
        'booking_prices' => DB::table('bookings')->where('total_price', '<', 0)->orWhere('refund_amount', '<', 0)->count(),
        'room_capacity' => DB::table('room_types')->whereRaw('max_guests < max_adults + max_children')->count(),
        'duplicate_reviews' => DB::table('reviews')->select('user_id', 'room_type_id')->groupBy('user_id', 'room_type_id')->havingRaw('COUNT(*) > 1')->get()->count(),
        'active_price_overlaps' => DB::table('price_settings as a')
            ->join('price_settings as b', function ($join): void {
                $join->on('a.id', '<', 'b.id')
                    ->on('a.start_date', '<=', 'b.end_date')
                    ->on('a.end_date', '>=', 'b.start_date');
            })->where('a.status', 1)->where('b.status', 1)->count(),
    ],
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
