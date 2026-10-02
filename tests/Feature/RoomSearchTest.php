<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoomSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_room_search_filters_by_name_dates_and_reserved_inventory(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(10, 0));
        $checkIn = now('Asia/Ho_Chi_Minh')->addDays(5)->toDateString();
        $checkOut = now('Asia/Ho_Chi_Minh')->addDays(7)->toDateString();

        $openType = $this->roomType('Deluxe Available');
        $this->room($openType, 'OPEN-1');

        $heldType = $this->roomType('Deluxe Held');
        $heldRoom = $this->room($heldType, 'HELD-1');
        $booking = $this->booking($checkIn, $checkOut);
        $booking->rooms()->attach($heldRoom->id);

        $response = $this->get(route('rooms.index', [
            'search' => 1,
            'q' => 'Deluxe',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 2,
            'children' => 0,
        ]));

        $response->assertOk()
            ->assertViewHas('rooms', fn ($rooms) => $rooms->pluck('type_name')->all() === ['Deluxe Available'])
            ->assertSee('name="check_in"', false)
            ->assertSee('name="adults"', false);
    }

    public function test_room_search_checks_capacity_and_rejects_invalid_date_ranges(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(10, 0));
        $checkIn = now('Asia/Ho_Chi_Minh')->addDays(5)->toDateString();
        $checkOut = now('Asia/Ho_Chi_Minh')->addDays(7)->toDateString();
        $type = $this->roomType('Compact');
        $this->room($type, 'CAP-1');

        $this->get(route('rooms.index', [
            'search' => 1,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 3,
            'children' => 0,
        ]))->assertOk()->assertViewHas('rooms', fn ($rooms) => $rooms->isEmpty());

        $this->get(route('rooms.index', [
            'search' => 1,
            'check_in' => $checkOut,
            'check_out' => $checkIn,
            'adults' => 1,
            'children' => 0,
        ]))->assertSessionHasErrors('check_out');
    }

    public function test_room_search_disallows_same_day_checkin_after_five_pm(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(17, 1));
        $today = now('Asia/Ho_Chi_Minh')->toDateString();

        $this->get(route('rooms.index', [
            'search' => 1,
            'check_in' => $today,
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'adults' => 1,
            'children' => 0,
        ]))->assertSessionHasErrors('check_in');
    }

    private function roomType(string $name): RoomType
    {
        return RoomType::create([
            'type_name' => $name,
            'price' => 200000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
        ]);
    }

    private function room(RoomType $type, string $number): Room
    {
        return Room::create([
            'room_number' => $number,
            'room_type_id' => $type->id,
            'floor' => 1,
            'status' => Room::STATUS_AVAILABLE,
        ]);
    }

    private function booking(string $checkIn, string $checkOut): Booking
    {
        $user = User::create([
            'username' => 'room-search@example.com',
            'fullname' => 'Search Guest',
            'email' => 'room-search@example.com',
            'phone' => '0912345678',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'verified' => true,
        ]);

        return Booking::create([
            'user_id' => $user->id,
            'customer_name' => $user->fullname,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 400000,
            'deposit_amount' => 200000,
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);
    }
}
