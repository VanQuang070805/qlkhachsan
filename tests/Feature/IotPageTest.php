<?php

namespace Tests\Feature;

use App\Models\FaceProfile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
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
            $table->string('status');
            $table->timestamp('actual_check_in')->nullable();
        });
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');
        });
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('room_id');
        });
        Schema::create('face_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('user_id')->nullable();
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

    public function test_receptionist_can_open_face_id_tools(): void
    {
        DB::table('users')->insert(['id' => 1, 'role' => 'receptionist']);
        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->get('/staff/iot')->assertOk()->assertSee('Face ID khách đang lưu trú')
            ->assertSee('Đăng ký Face ID')->assertSee('Full sync');
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

        $this->withSession(['user_id' => 1, 'auth_user_id' => 1])
            ->postJson('/staff/face-id/sessions', ['booking_id' => 10, 'consent' => true])
            ->assertOk()
            ->assertJson(['session_id' => 'test-session', 'target' => 15]);
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
        $profile = FaceProfile::create([
            'booking_id' => 10,
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
                'customer_name' => 'Khách test',
                'score' => 0.82,
            ]);
    }
}
