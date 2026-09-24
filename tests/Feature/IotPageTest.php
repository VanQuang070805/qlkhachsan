<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IotPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Only auth is needed here; historical migrations use MySQL ENUM.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->boolean('verified')->default(true);
            $table->string('password')->default('unused');
            $table->string('remember_token')->nullable();
        });
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/staff/iot')->assertRedirect('/login');
    }

    public function test_customer_cannot_open_iot_tools(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'customer']);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertForbidden();
    }

    public function test_receptionist_can_open_camera_diagnostics(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist']);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertOk()->assertSee('Thử camera laptop')
            ->assertSee('raspberry-pi/')->assertSee('Chưa xác nhận stream ESP32');
    }

    public function test_locked_staff_cannot_open_tools(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist', 'verified' => false]);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertRedirect('/login');
    }

    public function test_unsafe_camera_url_is_not_linked(): void
    {
        config(['iot.camera_stream_url' => 'javascript:alert(1)']);
        DB::table('users')->insert(['id' => 1, 'role' => 'admin']);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertOk()->assertDontSee('href="javascript:', false);
    }
}
