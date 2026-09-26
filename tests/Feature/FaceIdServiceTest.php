<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Booking;
use App\Models\FaceProfile;
use App\Models\FaceSyncJob;
use App\Models\Room;
use App\Services\FaceId\FaceIdService;
use App\Services\FaceId\FaceSyncService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FaceIdServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['face_id.sync_immediately' => false]);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->string('status');
            $table->date('check_out');
            $table->timestamp('actual_check_out')->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('deposit_amount', 12, 2)->default(0);
            $table->decimal('late_checkout_fee', 12, 2)->default(0);
            $table->boolean('waive_late_fee')->default(false);
            $table->timestamps();
        });
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number');
            $table->string('status')->default('occupied');
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
            $table->longText('embedding');
            $table->string('embedding_model');
            $table->unsignedSmallInteger('embedding_dimension');
            $table->unsignedSmallInteger('sample_count');
            $table->unsignedInteger('version');
            $table->boolean('active');
            $table->timestamp('consent_at');
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

    public function test_enrollment_encrypts_embedding_and_queues_add(): void
    {
        $booking = $this->booking();
        $profile = app(FaceIdService::class)->enroll(
            $booking,
            $this->roomFor($booking),
            array_fill(0, 128, 1.0),
            15,
            [
                'guest_name' => 'Nguyễn Văn A',
                'guest_cccd' => '012345678901',
                'guest_phone' => '0901234567',
            ],
        );

        $this->assertTrue($profile->active);
        $this->assertSame('Nguyễn Văn A', $profile->guest_name);
        $this->assertSame('012345678901', $profile->guest_cccd);
        $this->assertSame('0901234567', $profile->guest_phone);
        $this->assertCount(128, $profile->embedding);
        $this->assertEqualsWithDelta(1.0, $this->norm($profile->embedding), 0.0001);
        $this->assertStringNotContainsString('[', DB::table('face_profiles')->value('embedding'));
        $this->assertDatabaseHas('face_sync_queue', [
            'face_profile_id' => $profile->id,
            'action' => 'ADD',
            'status' => 'PENDING',
        ]);
    }

    public function test_room_can_have_multiple_face_ids(): void
    {
        $booking = $this->booking();
        $room = $this->roomFor($booking);
        $service = app(FaceIdService::class);
        $first = $service->enroll($booking, $room, $this->embedding(0));
        $second = $service->enroll($booking, $room, $this->embedding(1));

        $this->assertNotSame($first->id, $second->id);
        $this->assertSame(2, FaceProfile::where('room_id', $room->id)->where('active', true)->count());
        $this->assertSame(2, FaceSyncJob::where('action', 'ADD')->where('status', 'PENDING')->count());
    }

    public function test_same_face_cannot_be_registered_twice(): void
    {
        $firstBooking = $this->booking('501');
        $secondBooking = $this->booking('502');
        $service = app(FaceIdService::class);
        $service->enroll($firstBooking, $this->roomFor($firstBooking), array_fill(0, 128, 1.0));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Khuôn mặt này đã được đăng ký');
        $service->enroll($secondBooking, $this->roomFor($secondBooking), array_fill(0, 128, 1.0));
    }

    public function test_checkout_deactivates_profile_and_queues_delete(): void
    {
        $booking = $this->booking();
        $room = $this->roomFor($booking);
        $service = app(FaceIdService::class);
        $first = $service->enroll($booking, $room, $this->embedding(0));
        $second = $service->enroll($booking, $room, $this->embedding(1));
        $booking->update(['status' => 'completed']);

        $this->assertFalse($first->fresh()->active);
        $this->assertFalse($second->fresh()->active);
        $this->assertSame(2, FaceSyncJob::where('action', 'DELETE')->where('status', 'PENDING')->count());
    }

    public function test_cash_checkout_completes_booking_and_deletes_face_id(): void
    {
        $booking = $this->booking();
        $profile = app(FaceIdService::class)->enroll($booking, $this->roomFor($booking), array_fill(0, 128, 1.0));
        $request = Request::create('/staff/bookings/'.$booking->id.'/checkout-payment', 'POST', [
            'payment_method' => 'cash',
            'waive_late_fee' => false,
        ]);

        $response = app(PaymentController::class)->staffCheckoutPayment($request, $booking->id);

        $this->assertTrue($response->getData(true)['success']);
        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertFalse($profile->fresh()->active);
        $this->assertDatabaseHas('face_sync_queue', [
            'face_profile_id' => $profile->id,
            'action' => 'DELETE',
            'status' => 'PENDING',
        ]);
    }

    public function test_offline_pi_keeps_job_pending_for_retry(): void
    {
        config(['face_id.api_key' => 'test-secret', 'face_id.sync_retry_interval' => 5]);
        Http::fake(fn () => Http::failedConnection());
        $booking = $this->booking();
        $profile = app(FaceIdService::class)->enroll($booking, $this->roomFor($booking), array_fill(0, 128, 1.0));

        $result = app(FaceSyncService::class)->syncPending();

        $this->assertSame(1, $result['failed']);
        $job = FaceSyncJob::where('face_profile_id', $profile->id)->firstOrFail();
        $this->assertSame('PENDING', $job->status);
        $this->assertSame(1, $job->retry_count);
        $this->assertNotNull($job->next_attempt_at);
    }

    public function test_sync_reconciles_checkout_done_by_legacy_sql(): void
    {
        config(['face_id.api_key' => 'test-secret']);
        Http::fake(fn () => Http::response(['status' => 'deleted']));
        $booking = $this->booking();
        $profile = app(FaceIdService::class)->enroll($booking, $this->roomFor($booking), array_fill(0, 128, 1.0));

        DB::table('bookings')->where('id', $booking->id)->update(['status' => 'completed']);
        $result = app(FaceSyncService::class)->syncPending();

        $this->assertSame(1, $result['deactivated']);
        $this->assertFalse($profile->fresh()->active);
        $this->assertDatabaseHas('face_sync_queue', [
            'face_profile_id' => $profile->id,
            'action' => 'DELETE',
            'status' => 'SYNCED',
        ]);
        Http::assertSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_pi_sync_only_contains_room_501(): void
    {
        config(['face_id.api_key' => 'test-secret', 'face_id.pi_room_number' => '501']);
        Http::fake(fn () => Http::response([
            'received' => 1,
            'added' => 1,
            'updated' => 0,
            'deleted' => 0,
        ]));

        $booking501 = $this->booking('501');
        $booking502 = $this->booking('502');
        app(FaceIdService::class)->enroll($booking501, $this->roomFor($booking501), $this->embedding(0));
        app(FaceIdService::class)->enroll($booking502, $this->roomFor($booking502), $this->embedding(1));

        app(FaceSyncService::class)->fullSync();

        $this->assertSame(1, FaceSyncJob::count());
        Http::assertSent(function ($request) {
            $faces = $request->data()['faces'] ?? [];

            return str_ends_with($request->url(), '/api/faces/full-sync')
                && count($faces) === 1
                && $faces[0]['room'] === '501';
        });
    }

    public function test_room_501_add_and_delete_sync_immediately(): void
    {
        config([
            'face_id.api_key' => 'test-secret',
            'face_id.pi_room_number' => '501',
            'face_id.sync_immediately' => true,
        ]);
        Http::fake(fn ($request) => Http::response([
            'status' => $request->method() === 'DELETE' ? 'deleted' : 'added',
            'cache_count' => $request->method() === 'DELETE' ? 0 : 1,
        ]));

        $booking = $this->booking('501');
        $service = app(FaceIdService::class);
        $profile = $service->enroll($booking, $this->roomFor($booking), $this->embedding(0));

        $this->assertDatabaseHas('face_sync_queue', [
            'face_profile_id' => $profile->id,
            'action' => 'ADD',
            'status' => 'SYNCED',
        ]);

        $service->deactivateProfile($profile);

        $this->assertDatabaseHas('face_sync_queue', [
            'face_profile_id' => $profile->id,
            'action' => 'DELETE',
            'status' => 'SYNCED',
        ]);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/api/faces'));
        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_ends_with($request->url(), '/api/faces/'.$profile->id));
    }

    public function test_immediate_sync_failure_keeps_change_pending_for_scheduler(): void
    {
        config([
            'face_id.api_key' => 'test-secret',
            'face_id.pi_room_number' => '501',
            'face_id.sync_immediately' => true,
        ]);
        Http::fake(fn () => Http::failedConnection());

        $booking = $this->booking('501');
        $profile = app(FaceIdService::class)
            ->enroll($booking, $this->roomFor($booking), $this->embedding(0));

        $this->assertTrue($profile->active);
        $this->assertDatabaseHas('face_sync_queue', [
            'face_profile_id' => $profile->id,
            'action' => 'ADD',
            'status' => 'PENDING',
            'retry_count' => 1,
        ]);
    }

    private function booking(string $roomNumber = '501'): Booking
    {
        $booking = Booking::create([
            'customer_name' => 'Khách thử nghiệm',
            'status' => 'checked_in',
            'check_out' => now()->addDay()->toDateString(),
        ]);

        $roomId = DB::table('rooms')->insertGetId([
            'room_number' => $roomNumber,
            'status' => 'occupied',
        ]);
        DB::table('booking_rooms')->insert(['booking_id' => $booking->id, 'room_id' => $roomId]);

        return $booking;
    }

    private function roomFor(Booking $booking): Room
    {
        return $booking->rooms()->firstOrFail();
    }

    private function embedding(int $position): array
    {
        $embedding = array_fill(0, 128, 0.0);
        $embedding[$position] = 1.0;

        return $embedding;
    }

    private function norm(array $embedding): float
    {
        return sqrt(array_sum(array_map(fn ($value) => $value * $value, $embedding)));
    }
}
