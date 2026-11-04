<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\IntegrationOutbox;
use App\Models\PriceSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\CacheVersionService;
use App\Services\Chatbot\ChatbotCapabilityService;
use App\Services\Chatbot\LangGraphChatbotService;
use App\Services\OutboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DockerRestCapabilityOutboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_context_enforces_strict_boundary_between_customer_staff_and_admin_portals(): void
    {
        $customer = $this->createUser('cust-boundary@example.com', 'customer');
        $staff = $this->createUser('staff-boundary@example.com', 'receptionist');
        $admin = $this->createUser('admin-boundary@example.com', 'admin');

        // 1. On customer portal (PORTAL_CONTEXT=customer), /staff/* and /admin/* are rejected with 403
        $this->withHeader('X-Portal-Context', 'customer')
            ->getJson('/api/v1/staff/bookings')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'PORTAL_CONTEXT_MISMATCH');

        $this->withHeader('X-Portal-Context', 'customer')
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'PORTAL_CONTEXT_MISMATCH');

        // 2. On staff portal (PORTAL_CONTEXT=staff), / redirects to /staff/login, and /api/v1/admin/* is rejected
        $this->withHeader('X-Portal-Context', 'staff')
            ->get('/')
            ->assertRedirect(route('staff.login'));

        $this->withHeader('X-Portal-Context', 'staff')
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'PORTAL_CONTEXT_MISMATCH');

        // 3. On admin portal (PORTAL_CONTEXT=admin), / redirects to /admin/login, and /api/v1/customer/* is rejected
        $this->withHeader('X-Portal-Context', 'admin')
            ->get('/')
            ->assertRedirect(route('admin.login'));

        $this->withHeader('X-Portal-Context', 'admin')
            ->getJson('/api/v1/customer/me')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'PORTAL_CONTEXT_MISMATCH');
    }

    public function test_local_port_takes_precedence_over_client_supplied_portal_header(): void
    {
        $this->withHeader('X-Portal-Context', 'admin')
            ->getJson('http://127.0.0.1:18080/api/v1/admin/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'PORTAL_CONTEXT_MISMATCH');
    }

    public function test_login_pages_link_to_each_portal_host(): void
    {
        $this->get('http://127.0.0.1:18080/login')
            ->assertOk()
            ->assertSee(rtrim(config('services.portal.staff_url'), '/').'/staff/login', false)
            ->assertSee(rtrim(config('services.portal.admin_url'), '/').'/admin/login', false);

        $this->get('http://127.0.0.1:18081/staff/login')
            ->assertOk()
            ->assertSee(rtrim(config('services.portal.customer_url'), '/').'/login', false)
            ->assertSee(rtrim(config('services.portal.admin_url'), '/').'/admin/login', false);
    }

    public function test_rest_api_v1_public_customer_staff_admin_contracts_and_idempotency(): void
    {
        $roomType = RoomType::create([
            'type_name' => 'Suite Hoàng Gia',
            'price' => 1200000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
            'description' => 'Suite cao cấp',
        ]);

        $room = Room::create([
            'room_number' => 'S-501',
            'room_type_id' => $roomType->id,
            'floor' => 5,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        // Public catalog contract + X-Correlation-ID
        $pubRes = $this->withHeader('X-Correlation-ID', 'corr-test-123456')
            ->getJson('/api/v1/public/room-types')
            ->assertOk()
            ->assertHeader('X-Correlation-ID', 'corr-test-123456')
            ->assertJsonPath('meta.correlation_id', 'corr-test-123456')
            ->assertJsonPath('data.0.type_name', 'Suite Hoàng Gia')
            ->assertJsonPath('data.0.base_price_vnd', 1200000);

        $checkIn = now('Asia/Ho_Chi_Minh')->addDays(4)->toDateString();
        $checkOut = now('Asia/Ho_Chi_Minh')->addDays(6)->toDateString();

        // Public quote
        $this->postJson('/api/v1/public/quotes', [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'rooms' => 1,
            'guests' => 2,
        ])->assertOk()
          ->assertJsonPath('data.total_price_vnd', 2400000)
          ->assertJsonPath('data.deposit_amount_vnd', 1200000);

        // Customer booking with Idempotency-Key
        $customerA = $this->createUser('cust-a@example.com', 'customer');
        $customerB = $this->createUser('cust-b@example.com', 'customer');

        $payload = [
            'room_type_id' => $roomType->id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adult_count' => 2,
            'child_count' => 0,
            'room_count' => 1,
            'payment_method' => 'vietqr',
        ];

        $firstCreate = $this->withSession(['customer_user_id' => $customerA->id])
            ->withHeader('Idempotency-Key', 'idem-booking-001')
            ->postJson('/api/v1/customer/bookings', $payload)
            ->assertCreated();

        $bookingId = $firstCreate->json('data.id');
        $this->assertNotEmpty($bookingId);

        // Second request with the SAME Idempotency-Key and SAME payload replays without creating a 2nd booking
        $replayCreate = $this->withSession(['customer_user_id' => $customerA->id])
            ->withHeader('Idempotency-Key', 'idem-booking-001')
            ->postJson('/api/v1/customer/bookings', $payload)
            ->assertCreated()
            ->assertHeader('X-Idempotent-Replay', 'true');

        $this->assertSame($bookingId, $replayCreate->json('data.id'));
        $this->assertSame(1, Booking::count());

        // Same Idempotency-Key with DIFFERENT payload returns 409 IDEMPOTENCY_KEY_CONFLICT
        $this->withSession(['customer_user_id' => $customerA->id])
            ->withHeader('Idempotency-Key', 'idem-booking-001')
            ->postJson('/api/v1/customer/bookings', array_merge($payload, ['adult_count' => 1]))
            ->assertConflict()
            ->assertJsonPath('error.code', 'IDEMPOTENCY_KEY_CONFLICT');

        // Customer B CANNOT read or cancel Customer A's booking
        $this->withSession(['customer_user_id' => $customerB->id])
            ->getJson("/api/v1/customer/bookings/{$bookingId}")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'FORBIDDEN_BOOKING_ACCESS');

        $this->withSession(['customer_user_id' => $customerB->id])
            ->postJson("/api/v1/customer/bookings/{$bookingId}/cancellations")
            ->assertForbidden();
    }

    public function test_chatbot_capability_token_isolates_customer_bookings_and_blocks_unauthorized_tools(): void
    {
        config([
            'services.langgraph.tool_api_key' => 'tool-secret-cap-test',
        ]);

        $customerA = $this->createUser('cap-a@example.com', 'customer');
        $customerB = $this->createUser('cap-b@example.com', 'customer');

        $bookingA = Booking::create([
            'user_id' => $customerA->id,
            'customer_name' => 'Customer A',
            'customer_email' => $customerA->email,
            'customer_phone' => '0911111111',
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 1500000,
            'deposit_amount' => 750000,
            'payment_status' => 'pending',
            'status' => 'confirmed',
        ]);

        $bookingB = Booking::create([
            'user_id' => $customerB->id,
            'customer_name' => 'Customer B',
            'customer_email' => $customerB->email,
            'customer_phone' => '0922222222',
            'check_in' => now()->addDays(4)->toDateString(),
            'check_out' => now()->addDays(6)->toDateString(),
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 2200000,
            'deposit_amount' => 1100000,
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);

        // 1. Calling my-bookings without capability token is rejected with 403 and records security audit outbox event
        $this->postJson('/internal/v1/chatbot/tools/my-bookings', [], [
            'Authorization' => 'Bearer tool-secret-cap-test',
        ])->assertForbidden();

        $this->assertDatabaseHas('integration_outbox', [
            'topic' => OutboxService::TOPIC_SECURITY_AUDIT,
            'event_type' => 'capability_rejected',
        ]);

        // 2. Issue capability token for Customer A; even if request body tries to pass customerB's ID, only Customer A's booking is returned!
        $reqA = \Illuminate\Http\Request::create('/chatbot/stream', 'POST');
        $session = app('session.store');
        $session->put('customer_user_id', $customerA->id);
        $reqA->setLaravelSession($session);

        $capService = app(ChatbotCapabilityService::class);
        $capA = $capService->issueForRequest($reqA, 'participant-a');

        $resA = $this->postJson('/internal/v1/chatbot/tools/my-bookings', [
            'user_id' => $customerB->id, // Prompt injection attempt
            'customer_email' => $customerB->email,
        ], [
            'Authorization' => 'Bearer tool-secret-cap-test',
            'X-Chatbot-Capability' => $capA['token'],
        ])->assertOk();

        $resA->assertJsonCount(1, 'bookings')
            ->assertJsonPath('bookings.0.booking_id', $bookingA->id)
            ->assertJsonPath('bookings.0.total_price_vnd', 1500000);

        $this->assertNotContains($bookingB->id, array_column($resA->json('bookings'), 'booking_id'));
        $this->assertStringNotContainsString('2200000', $resA->getContent());
    }

    public function test_dedupe_fingerprint_and_cache_invalidation_work_across_scopes_and_mutations(): void
    {
        $langGraph = app(LangGraphChatbotService::class);
        $versions = app(CacheVersionService::class);

        $fpPublic1 = $langGraph->buildFingerprint('Cho hỏi giờ nhận phòng?', [], 'customer:aaa');
        $fpPublic2 = $langGraph->buildFingerprint('  cho hỏi   giờ nhận phòng? ', [], 'customer:bbb');

        // Public FAQ question shares the exact same fingerprint across different customers/guests
        $this->assertSame($fpPublic1, $fpPublic2);

        // Personal booking question has distinct fingerprints per customer scope
        $fpPersonalA = $langGraph->buildFingerprint('Kiểm tra đơn của tôi', [], 'customer:aaa');
        $fpPersonalB = $langGraph->buildFingerprint('Kiểm tra đơn của tôi', [], 'customer:bbb');
        $this->assertNotSame($fpPersonalA, $fpPersonalB);

        // Updating a RoomType or PriceSetting bumps catalogVersion and changes the fingerprint + writes to integration_outbox
        $initialCatalogVersion = $versions->catalogVersion();
        PriceSetting::create([
            'name' => 'Lễ Tết 2026',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(15)->toDateString(),
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'adjustment_type' => 'percent',
            'adjustment_value' => 15,
            'is_active' => true,
        ]);

        $this->assertGreaterThan($initialCatalogVersion, $versions->catalogVersion());
        $fpAfterPriceChange = $langGraph->buildFingerprint('Cho hỏi giờ nhận phòng?', [], 'customer:aaa');
        $this->assertNotSame($fpPublic1, $fpAfterPriceChange);

        // Outbox must never contain secrets or raw message text
        app(OutboxService::class)->recordChatbotEvent('completed', [
            'actor_type' => 'customer',
            'api_key' => 'SECRET_KEY_MUST_BE_STRIPPED',
            'raw_question' => 'SENSITIVE_QUESTION_MUST_BE_STRIPPED',
            'duration_ms' => 120,
        ]);

        $latestOutbox = IntegrationOutbox::latest('id')->first();
        $this->assertNotNull($latestOutbox);
        $payloadJson = json_encode($latestOutbox->payload);
        $this->assertStringNotContainsString('SECRET_KEY_MUST_BE_STRIPPED', $payloadJson);
        $this->assertStringNotContainsString('SENSITIVE_QUESTION_MUST_BE_STRIPPED', $payloadJson);
    }

    private function createUser(string $email, string $role): User
    {
        return User::create([
            'username' => $email,
            'fullname' => ucfirst($role).' User',
            'email' => $email,
            'phone' => '0909123456',
            'password' => Hash::make('Password123!'),
            'role' => $role,
            'verified' => true,
        ]);
    }
}
