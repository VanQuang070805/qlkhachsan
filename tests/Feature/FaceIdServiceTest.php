<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\FaceProfile;
use App\Models\FaceSyncJob;
use App\Http\Controllers\PaymentController;
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('customer_name');
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
        });
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('room_id');
        });
        Schema::create('face_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('booking_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
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
        $profile = app(FaceIdService::class)->enroll($booking, array_fill(0, 128, 1.0), 15);

        $this->assertTrue($profile->active);
        $this->assertCount(128, $profile->embedding);
        $this->assertEqualsWithDelta(1.0, $this->norm($profile->embedding), 0.0001);
        $this->assertStringNotContainsString('[', DB::table('face_profiles')->value('embedding'));
        $this->assertDatabaseHas('face_sync_queue', [
            'face_profile_id' => $profile->id,
            'action' => 'ADD',
            'status' => 'PENDING',
        ]);
    }

    public function test_update_replaces_duplicate_pending_job(): void
    {
        $booking = $this->booking();
        $service = app(FaceIdService::class);
        $profile = $service->enroll($booking, array_fill(0, 128, 1.0));
        $service->enroll($booking, array_fill(0, 128, 2.0));

        $this->assertSame(1, FaceSyncJob::where('face_profile_id', $profile->id)->where('status', 'PENDING')->count());
        $this->assertDatabaseHas('face_sync_queue', ['face_profile_id' => $profile->id, 'action' => 'UPDATE']);
    }

    public function test_same_face_cannot_be_assigned_to_another_active_booking(): void
    {
        $service = app(FaceIdService::class);
        $service->enroll($this->booking(), array_fill(0, 128, 1.0));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Khuôn mặt này đã được đăng ký');
        $service->enroll($this->booking(), array_fill(0, 128, 1.0));
    }

    public function test_checkout_deactivates_profile_and_queues_delete(): void
    {
        $booking = $this->booking();
        $profile = app(FaceIdService::class)->enroll($booking, array_fill(0, 128, 1.0));
        $booking->update(['status' => 'completed']);

        $this->assertFalse($profile->fresh()->active);
        $this->assertDatabaseHas('face_sync_queue', [
            'face_profile_id' => $profile->id,
            'action' => 'DELETE',
            'status' => 'PENDING',
        ]);
    }

    public function test_cash_checkout_completes_booking_and_deletes_face_id(): void
    {
        $booking = $this->booking();
        $profile = app(FaceIdService::class)->enroll($booking, array_fill(0, 128, 1.0));
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
        $profile = app(FaceIdService::class)->enroll($this->booking(), array_fill(0, 128, 1.0));

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
        $profile = app(FaceIdService::class)->enroll($booking, array_fill(0, 128, 1.0));

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

    private function booking(): Booking
    {
        return Booking::create([
            'customer_name' => 'Khách thử nghiệm',
            'status' => 'checked_in',
            'check_out' => now()->addDay()->toDateString(),
        ]);
    }

    private function norm(array $embedding): float
    {
        return sqrt(array_sum(array_map(fn ($value) => $value * $value, $embedding)));
    }
}
