<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IotCleaningSwitchApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'iot.device_api_key' => 'test-device-key',
            'iot.cleaning_room_number' => '501',
        ]);

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');
            $table->string('status')->default('occupied');
            $table->boolean('needs_cleaning')->default(false);
            $table->timestamp('cleaning_requested_at')->nullable();
        });

        DB::table('rooms')->insert([
            'id' => 501,
            'room_number' => '501',
            'status' => 'occupied',
        ]);
    }

    public function test_switch_requires_the_device_key(): void
    {
        $this->postJson('/api/iot/rooms/501/cleaning-request', ['needs_cleaning' => true])
            ->assertUnauthorized();
    }

    public function test_device_can_check_api_and_current_room_state(): void
    {
        $this->getJson('/api/iot/rooms/501/cleaning-request', [
            'X-API-Key' => 'test-device-key',
        ])->assertOk()->assertJson([
            'success' => true,
            'room_number' => '501',
            'status' => 'occupied',
            'needs_cleaning' => false,
        ]);
    }

    public function test_switch_can_turn_room_501_cleaning_request_on_and_off(): void
    {
        $headers = ['X-API-Key' => 'test-device-key'];

        $this->postJson('/api/iot/rooms/501/cleaning-request', ['needs_cleaning' => true], $headers)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'room_number' => '501',
                'status' => 'occupied',
                'needs_cleaning' => true,
            ]);

        $this->assertDatabaseHas('rooms', [
            'room_number' => '501',
            'status' => 'occupied',
            'needs_cleaning' => true,
        ]);

        DB::table('rooms')->where('room_number', '501')->update(['status' => 'cleaning']);

        $this->postJson('/api/iot/rooms/501/cleaning-request', ['needs_cleaning' => false], $headers)
            ->assertOk()
            ->assertJson([
                'status' => 'available',
                'needs_cleaning' => false,
            ]);
    }

    public function test_device_cannot_control_another_room(): void
    {
        $this->postJson('/api/iot/rooms/502/cleaning-request', ['needs_cleaning' => true], [
            'X-API-Key' => 'test-device-key',
        ])->assertForbidden();
    }
}
