<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\Booking;
use App\Models\RoomType;
use App\Models\User;
use App\Models\PriceSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoomCatalogConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_room_cards_follow_live_capacity_price_and_reserved_room_count(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(10, 0));
        $type = RoomType::create([
            'type_name' => 'Live Family Room', 'price' => 50000,
            'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3,
        ]);
        $reservedRoom = Room::create(['room_number' => 'LIVE-101', 'room_type_id' => $type->id, 'floor' => 1, 'status' => Room::STATUS_AVAILABLE]);
        $otherRoom = Room::create(['room_number' => 'LIVE-102', 'room_type_id' => $type->id, 'floor' => 1, 'status' => Room::STATUS_AVAILABLE]);
        $customer = User::create([
            'username' => 'live-room-customer', 'fullname' => 'Live Guest', 'email' => 'live-room@example.test',
            'phone' => '0912345678', 'password' => Hash::make('password123'), 'role' => 'customer', 'verified' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $customer->id, 'customer_name' => $customer->fullname,
            'customer_email' => $customer->email, 'customer_phone' => $customer->phone,
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'adult_count' => 2, 'child_count' => 1, 'total_price' => 50000,
            'deposit_amount' => 25000, 'payment_status' => 'paid', 'status' => 'confirmed',
        ]);
        $booking->rooms()->attach($reservedRoom->id);
        PriceSetting::create([
            'name' => 'Live pricing', 'start_date' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'end_date' => now('Asia/Ho_Chi_Minh')->toDateString(), 'adjustment_type' => 'percent',
            'adjustment_value' => 20, 'status' => true,
        ]);

        $this->get(route('home'))->assertOk()->assertSee('Live Family Room')->assertSee('60.000 đ')
            ->assertSee('3 khách')->assertSee('1 phòng trống');

        $type->update(['price' => 80000, 'max_guests' => 4]);
        $otherRoom->update(['status' => Room::STATUS_MAINTENANCE]);
        $this->get(route('home'))->assertOk()->assertSee('96.000 đ')
            ->assertSee('4 khách')->assertSee('0 phòng trống');
    }

    public function test_home_phone_preview_uses_the_actual_price_policy_for_its_dates(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(10, 0));
        RoomType::query()->create([
            'type_name' => 'Preview Room', 'price' => 50000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        PriceSetting::query()->create([
            'name' => 'Preview date', 'start_date' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'end_date' => now('Asia/Ho_Chi_Minh')->toDateString(), 'adjustment_type' => 'percent',
            'adjustment_value' => 20, 'status' => true,
        ]);
        $this->get(route('home'))->assertOk()->assertSee('60.000đ/đêm')->assertSee('Giá tham khảo');
    }

    public function test_admin_room_type_changes_are_read_from_database_in_customer_and_staff_views(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(10, 0));
        Storage::fake('public');

        $admin = $this->internalAdmin();
        $type = RoomType::query()->create([
            'type_name' => 'Consistency Original',
            'price' => 20000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
            'description' => 'Original description',
            'image' => 'images/rooms/original.jpg',
        ]);
        Room::query()->create([
            'room_number' => '101', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => Room::STATUS_AVAILABLE,
        ]);

        $this->asInternal($admin)->patch(route('admin.room-types.update', $type), [
            'type_name' => 'Consistency Updated',
            'price' => 50000,
            'max_adults' => 3,
            'max_children' => 2,
            'max_guests' => 5,
            'description' => 'Updated description',
            'amenities' => [],
            'image_file' => UploadedFile::fake()->image('updated-room.png'),
        ])->assertRedirect()->assertSessionHas('success');

        $updated = $type->fresh();
        $this->assertSame('Consistency Updated', $updated->type_name);
        $this->assertSame('50000.00', $updated->price);
        $this->assertSame(3, $updated->max_adults);
        $this->assertSame(2, $updated->max_children);
        $this->assertSame(5, $updated->max_guests);
        $this->assertStringStartsWith('storage/room-types/', $updated->image);
        Storage::disk('public')->assertExists(substr($updated->image, strlen('storage/')));

        $this->get(route('rooms.index'))
            ->assertOk()
            ->assertSee('Consistency Updated')
            ->assertSee('50.000 đ')
            ->assertSee('5 khách tối đa')
            ->assertSee('Updated description')
            ->assertSee($updated->image, false);

        $checkIn = now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString();
        $checkOut = now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString();
        $this->get(route('rooms.detail', [
            'id' => $updated->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 1,
            'children' => 0,
        ]))
            ->assertOk()
            ->assertSee('Consistency Updated')
            ->assertSee('50.000')
            ->assertSee('Updated description')
            ->assertSee($updated->image, false)
            ->assertDontSee('1.08', false)
            ->assertDontSee('Wi-Fi 6E 1Gbps');

        $this->getJson(route('rooms.detail', [
            'id' => $updated->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ]))->assertOk()->assertJsonPath('nightly_price', 50000);

        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('Consistency Updated')
            ->assertSee('50.000');

        $this->asInternal($admin)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee('Consistency Updated')
            ->assertSee('50000');

        $this->travelBack();
    }

    public function test_room_type_filter_options_are_database_driven_and_multi_select_is_or_filtered(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(10, 0));
        foreach (['Dynamic Room One', 'Dynamic Room Two'] as $index => $name) {
            $type = RoomType::query()->create([
                'type_name' => $name, 'price' => 30000,
                'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3,
            ]);
            Room::query()->create([
                'room_number' => '10'.($index + 1), 'room_type_id' => $type->id,
                'floor' => 1, 'status' => Room::STATUS_AVAILABLE,
            ]);
        }

        $checkIn = now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString();
        $checkOut = now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString();
        $this->get(route('rooms.index', [
            'search' => 1,
            'q' => 'Dynamic Room One,Dynamic Room Two',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 1,
            'children' => 0,
        ]))
            ->assertOk()
            ->assertSee('Dynamic Room One')
            ->assertSee('Dynamic Room Two')
            ->assertSee('value="Dynamic Room One"', false)
            ->assertSee('value="Dynamic Room Two"', false);

        $this->travelBack();
    }

    private function internalAdmin(): User
    {
        return User::query()->create([
            'username' => 'catalog-admin@example.test',
            'fullname' => 'Catalog Admin',
            'email' => 'catalog-admin@example.test',
            'phone' => '0912345678',
            'password' => Hash::make('123456'),
            'role' => 'admin',
            'verified' => true,
        ]);
    }

    private function asInternal(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'staff_user_id' => $user->id,
            'auth_user_id' => $user->id,
            'user_id' => $user->id,
            'user' => ['id' => $user->id, 'role' => $user->role, 'fullname' => $user->fullname, 'verified' => true],
        ]);
    }
}
