<?php

namespace Tests\Feature;

use App\Models\FaceProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('status');
            $table->timestamp('actual_check_in')->nullable();
        });
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');
            $table->string('status')->default('occupied');
            $table->boolean('needs_cleaning')->default(false);
            $table->timestamp('cleaning_requested_at')->nullable();
        });
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('room_id');
        });
        Schema::create('face_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('room_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_cccd', 20)->nullable();
            $table->string('guest_phone', 30)->nullable();
            $table->longText('embedding')->nullable();
            $table->string('embedding_model')->default('sface_2021dec');
            $table->unsignedSmallInteger('embedding_dimension')->default(128);
            $table->unsignedSmallInteger('sample_count')->default(15);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active');
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });
        Schema::create('face_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->uuid('face_profile_id');
            $table->string('action');
            $table->string('status');
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_guests_are_redirected_to_internal_login(): void
    {
        $this->get('/staff/iot')->assertRedirect('/internalauth/login');
    }

    public function test_customer_is_redirected_to_internal_login_for_iot_tools(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'customer']);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertRedirect('/internalauth/login');
    }

    public function test_receptionist_can_open_face_id_tools(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist']);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertOk()->assertSee('Test khuôn mặt đã đăng ký')
            ->assertSee('Test khuôn mặt')->assertSee('Full sync');
    }

    public function test_locked_staff_cannot_open_tools(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist', 'verified' => false]);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertRedirect('/internalauth/login');
    }

    public function test_pi_camera_module_is_shown_without_an_external_stream_link(): void
    {
        config(['iot.pi_camera_model' => 'Raspberry Pi Camera Module Rev 1.3']);
        DB::table('users')->insert(['id' => 1, 'role' => 'admin']);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertOk()
            ->assertSee('Raspberry Pi Camera Module Rev 1.3')
            ->assertDontSee('ESP32-CAM');
    }

    public function test_receptionist_can_start_enrollment_for_checked_in_booking(): void
    {
        Http::fake([
            'http://127.0.0.1:8001/api/enrollment-sessions' => Http::response([
                'session_id' => 'test-session',
                'target' => 15,
            ]),
        ]);
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist']);
        DB::table('bookings')->insert([
            'id' => 10,
            'customer_name' => 'Khách test',
            'status' => 'checked_in',
            'actual_check_in' => now(),
        ]);
        DB::table('rooms')->insert(['id' => 501, 'room_number' => '501', 'status' => 'occupied']);
        DB::table('booking_rooms')->insert(['booking_id' => 10, 'room_id' => 501]);

        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->postJson('/staff/face-id/sessions', [
                'booking_id' => 10,
                'room_id' => 501,
                'guest_name' => 'Khách test',
                'guest_cccd' => '012345678901',
                'guest_phone' => '0901234567',
                'consent' => true,
            ])
            ->assertOk()
            ->assertJson(['session_id' => 'test-session', 'target' => 15]);
    }

    public function test_receptionist_can_list_registered_guests_for_a_room(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist']);
        DB::table('bookings')->insert([
            'id' => 10,
            'customer_name' => 'Khách test',
            'status' => 'checked_in',
            'actual_check_in' => now(),
        ]);
        DB::table('rooms')->insert(['id' => 501, 'room_number' => '501', 'status' => 'occupied']);
        DB::table('booking_rooms')->insert(['booking_id' => 10, 'room_id' => 501]);
        FaceProfile::create([
            'booking_id' => 10,
            'room_id' => 501,
            'guest_name' => 'Nguyễn Văn A',
            'guest_cccd' => '012345678901',
            'guest_phone' => '0901234567',
            'embedding' => array_fill(0, 128, 0.01),
            'embedding_dimension' => 128,
            'active' => true,
            'consent_at' => now(),
        ]);

        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->getJson('/staff/face-id/profiles?booking_id=10&room_id=501')
            ->assertOk()
            ->assertJsonPath('room_number', '501')
            ->assertJsonPath('profiles.0.guest_name', 'Nguyễn Văn A')
            ->assertJsonPath('profiles.0.guest_cccd', '012345678901')
            ->assertJsonPath('profiles.0.guest_phone', '0901234567');
    }

    public function test_receptionist_can_test_a_saved_face_id(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist']);
        DB::table('bookings')->insert([
            'id' => 10,
            'customer_name' => 'Khách test',
            'status' => 'checked_in',
            'actual_check_in' => now(),
        ]);
        DB::table('rooms')->insert(['id' => 501, 'room_number' => '501', 'status' => 'occupied']);
        DB::table('booking_rooms')->insert(['booking_id' => 10, 'room_id' => 501]);
        $profile = FaceProfile::create([
            'booking_id' => 10,
            'room_id' => 501,
            'guest_name' => 'Nguyễn Văn B',
            'guest_cccd' => '001234567890',
            'guest_phone' => '0912345678',
            'embedding' => array_fill(0, 128, 0.01),
            'embedding_dimension' => 128,
            'active' => true,
            'consent_at' => now(),
        ]);
        Http::fake([
            'http://127.0.0.1:8001/api/recognize' => Http::response([
                'matched' => true,
                'customer_id' => $profile->id,
                'score' => 0.82,
                'threshold' => 0.363,
            ]),
        ]);

        $image = UploadedFile::fake()->image('face.jpg', 200, 200);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->post('/staff/face-id/recognize', ['frame' => $image], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJson([
                'matched' => true,
                'booking_id' => 10,
                'customer_name' => 'Nguyễn Văn B',
                'guest_cccd' => '001234567890',
                'guest_phone' => '0912345678',
                'score' => 0.82,
            ]);
    }

    public function test_receptionist_can_update_and_delete_registered_guest(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist']);
        DB::table('bookings')->insert([
            'id' => 10,
            'customer_name' => 'Khách test',
            'status' => 'checked_in',
            'actual_check_in' => now(),
        ]);
        DB::table('rooms')->insert(['id' => 501, 'room_number' => '501', 'status' => 'occupied']);
        DB::table('booking_rooms')->insert(['booking_id' => 10, 'room_id' => 501]);
        $profile = FaceProfile::create([
            'booking_id' => 10,
            'room_id' => 501,
            'guest_name' => 'Tên cũ',
            'embedding' => array_fill(0, 128, 0.01),
            'embedding_dimension' => 128,
            'active' => true,
            'consent_at' => now(),
        ]);
        $session = ['user_id' => 1, 'auth_user_id' => 1];

        $this->withSession($session)->patchJson('/staff/face-id/profiles/'.$profile->id, [
            'guest_name' => 'Tên mới',
            'guest_cccd' => '012345678901',
            'guest_phone' => '0909999999',
        ])->assertOk()->assertJsonPath('profile.guest_name', 'Tên mới');

        $this->assertDatabaseHas('face_profiles', [
            'id' => $profile->id,
            'guest_name' => 'Tên mới',
            'guest_cccd' => '012345678901',
        ]);
        $this->withSession($session)
            ->deleteJson('/staff/face-id/profiles/'.$profile->id)
            ->assertOk();
        $this->assertDatabaseHas('face_profiles', ['id' => $profile->id, 'active' => false]);
    }
}
