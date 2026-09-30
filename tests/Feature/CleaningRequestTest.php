<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CleaningRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role')->default('customer');
            $table->boolean('verified')->default(true);
            $table->string('password')->default('unused');
            $table->string('otp_code')->nullable();
            $table->string('remember_token')->nullable();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status');
        });
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');
            $table->integer('floor')->default(1);
            $table->string('status')->default('occupied');
            $table->boolean('needs_cleaning')->default(false);
            $table->timestamp('cleaning_requested_at')->nullable();
        });
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('room_id');
        });
    }

    public function test_checked_in_guest_can_turn_cleaning_request_on_and_off(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'customer', 'verified' => true]);
        DB::table('bookings')->insert(['id' => 10, 'user_id' => 1, 'status' => 'checked_in']);
        DB::table('rooms')->insert(['id' => 101, 'room_number' => '101', 'status' => 'occupied']);
        DB::table('booking_rooms')->insert(['booking_id' => 10, 'room_id' => 101]);

        $session = [
            'user_id' => 1,
            'auth_user_id' => 1,
            'user' => ['verified' => true],
        ];

        $this->withSession($session)
            ->patchJson('/booking/10/rooms/101/cleaning-request', ['needs_cleaning' => true])
            ->assertOk()
            ->assertJson(['success' => true, 'needs_cleaning' => true]);
        $this->assertDatabaseHas('rooms', ['id' => 101, 'status' => 'occupied', 'needs_cleaning' => true]);

        $this->withSession($session)
            ->patchJson('/booking/10/rooms/101/cleaning-request', ['needs_cleaning' => false])
            ->assertOk()
            ->assertJson(['success' => true, 'needs_cleaning' => false]);
        $this->assertDatabaseHas('rooms', ['id' => 101, 'status' => 'occupied', 'needs_cleaning' => false]);
    }

    public function test_guest_cannot_change_another_guests_room(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'customer', 'verified' => true]);
        DB::table('bookings')->insert(['id' => 10, 'user_id' => 999, 'status' => 'checked_in']);
        DB::table('rooms')->insert(['id' => 101, 'room_number' => '101', 'status' => 'occupied']);
        DB::table('booking_rooms')->insert(['booking_id' => 10, 'room_id' => 101]);

        $this->withSession([
            'user_id' => 1,
            'auth_user_id' => 1,
            'user' => ['verified' => true],
        ])->patchJson('/booking/10/rooms/101/cleaning-request', ['needs_cleaning' => true])
            ->assertForbidden();

        $this->assertDatabaseHas('rooms', ['id' => 101, 'needs_cleaning' => false]);
    }

    public function test_receptionist_sees_notifications_and_completing_cleaning_keeps_occupied_room_occupied(): void
    {
        DB::table('users')->insert(['id' => 2, 'role' => 'receptionist', 'verified' => true]);
        DB::table('rooms')->insert([
            'id' => 101,
            'room_number' => '101',
            'floor' => 1,
            'status' => 'occupied',
            'needs_cleaning' => true,
            'cleaning_requested_at' => now(),
        ]);
        $session = ['user_id' => 2, 'auth_user_id' => 2];

        $this->withSession($session)
            ->getJson('/staff/cleaning-notifications')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('rooms.0.room_number', '101')
            ->assertJsonPath('rooms.0.source', 'Khách yêu cầu');

        $this->withSession($session)
            ->postJson('/staff/reception/update-status', ['room_id' => 101, 'status' => 'available'])
            ->assertOk()
            ->assertJsonPath('new_status', 'occupied');

        $this->assertDatabaseHas('rooms', [
            'id' => 101,
            'status' => 'occupied',
            'needs_cleaning' => false,
        ]);
    }

    public function test_completing_cleaning_after_checkout_makes_room_available(): void
    {
        DB::table('users')->insert(['id' => 2, 'role' => 'receptionist', 'verified' => true]);
        DB::table('rooms')->insert([
            'id' => 102,
            'room_number' => '102',
            'status' => 'cleaning',
            'needs_cleaning' => true,
            'cleaning_requested_at' => now(),
        ]);

        $this->withSession(['user_id' => 2, 'auth_user_id' => 2])
            ->postJson('/staff/reception/update-status', ['room_id' => 102, 'status' => 'available'])
            ->assertOk()
            ->assertJsonPath('new_status', 'available');

        $this->assertDatabaseHas('rooms', [
            'id' => 102,
            'status' => 'available',
            'needs_cleaning' => false,
        ]);
    }
}
