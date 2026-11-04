<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\KnowledgeChunk;
use App\Models\PriceSetting;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiLangGraphChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_streams_real_incremental_deltas_and_reuses_server_thread_id(): void
    {
        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
            'services.langgraph.internal_api_key' => 'secret-internal-lg-key',
            'services.langgraph.retrieval_assistant_id' => 'retrieval_graph',
            'services.gemini.chat_model' => 'gemini-2.5-flash',
        ]);

        $ssePayload = implode("\n", [
            'event: messages/partial',
            'data: ' . json_encode([['content' => 'Xin chào quý khách, ']], JSON_UNESCAPED_UNICODE),
            '',
            'event: messages/partial',
            'data: ' . json_encode([['content' => 'Xin chào quý khách, giờ nhận phòng là 12:00 - 16:00.']], JSON_UNESCAPED_UNICODE),
            '',
            'event: updates',
            'data: ' . json_encode([
                'retrieveDocuments' => [
                    'documents' => [
                        ['metadata' => ['source' => 'Rosaliza Hotel · Nhận và trả phòng']],
                    ],
                ],
            ], JSON_UNESCAPED_UNICODE),
            '',
            'event: end',
            'data: {}',
            '',
        ]);

        Http::fake([
            '127.0.0.1:2024/threads' => Http::response(['thread_id' => 'lg-thread-001'], 200),
            '127.0.0.1:2024/threads/lg-thread-001/runs/stream' => Http::response($ssePayload, 200, [
                'Content-Type' => 'text/event-stream',
            ]),
        ]);

        $firstResponse = $this->post(route('chatbot.stream'), [
            'message' => 'Cho tôi hỏi giờ nhận phòng?',
        ]);

        $firstResponse->assertOk();
        $streamed = $firstResponse->streamedContent();

        $this->assertStringContainsString('"delta":"Xin chào quý khách, "', $streamed);
        $this->assertStringContainsString('"delta":"giờ nhận phòng là 12:00 - 16:00."', $streamed);
        $this->assertStringContainsString('"done":true', $streamed);
        $this->assertStringContainsString('Rosaliza Hotel · Nhận và trả phòng', $streamed);
        $this->assertStringNotContainsString('secret-internal-lg-key', $streamed);
        $this->assertSame('lg-thread-001', session('chatbot.langgraph_thread_id'));

        // Turn 2 should reuse thread lg-thread-001 without creating a second thread
        $secondResponse = $this->post(route('chatbot.stream'), [
            'message' => 'Còn trả phòng muộn thì sao?',
        ]);
        $secondResponse->assertOk();
        $secondResponse->streamedContent();

        Http::assertSentCount(3); // 1 thread creation + 2 stream runs
    }

    public function test_chatbot_returns_explicit_error_when_langgraph_backend_is_unavailable(): void
    {
        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
        ]);

        Http::fake([
            '127.0.0.1:2024/*' => Http::response(['error' => 'upstream down'], 503),
        ]);

        $this->postJson(route('chatbot.api'), [
            'message' => 'Giá phòng hôm nay thế nào?',
        ])->assertStatus(503)
          ->assertJsonPath('source', 'error');

        $stream = $this->post(route('chatbot.stream'), [
            'message' => 'Giá phòng hôm nay thế nào?',
        ]);
        $stream->assertOk();
        $content = $stream->streamedContent();
        $this->assertStringContainsString('"error"', $content);
        $this->assertStringNotContainsString('"done":true', $content);
    }

    public function test_internal_room_tools_require_secret_and_compute_live_availability_and_pricing(): void
    {
        config([
            'services.langgraph.tool_api_key' => 'tool-secret-999',
        ]);

        $deluxe = RoomType::create([
            'type_name' => 'Phòng Đôi Deluxe',
            'price' => 450000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
            'description' => 'Phòng đôi yên tĩnh',
        ]);

        $room1 = Room::create([
            'room_number' => 'DLX-101',
            'room_type_id' => $deluxe->id,
            'floor' => 1,
            'status' => Room::STATUS_AVAILABLE,
        ]);
        $room2 = Room::create([
            'room_number' => 'DLX-102',
            'room_type_id' => $deluxe->id,
            'floor' => 1,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        $checkIn = now('Asia/Ho_Chi_Minh')->addDays(5)->toDateString();
        $checkOut = now('Asia/Ho_Chi_Minh')->addDays(7)->toDateString();

        PriceSetting::create([
            'name' => 'Phụ thu cuối tuần kiểm thử',
            'start_date' => $checkIn,
            'end_date' => $checkOut,
            'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
            'adjustment_type' => 'percent',
            'adjustment_value' => 10,
            'is_active' => true,
        ]);

        // Unauthenticated tool request must fail
        $this->postJson('/api/chatbot/tools/rooms/search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
        ])->assertUnauthorized();

        // Active 10-minute hold on room1 reduces available_rooms to 1
        $customer = User::create([
            'username' => 'hold-guest@example.com',
            'fullname' => 'Hold Guest',
            'email' => 'hold-guest@example.com',
            'phone' => '0912345678',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'verified' => true,
        ]);
        $booking = Booking::create([
            'user_id' => $customer->id,
            'customer_name' => $customer->fullname,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 990000,
            'deposit_amount' => 495000,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
        $booking->rooms()->attach($room1->id);

        $searchRes = $this->postJson('/api/chatbot/tools/rooms/search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 2,
            'children' => 0,
            'room_count' => 1,
        ], [
            'Authorization' => 'Bearer tool-secret-999',
        ])->assertOk();

        $searchRes->assertJsonPath('nights', 2)
            ->assertJsonPath('available_room_types.0.type_name', 'Phòng Đôi Deluxe')
            ->assertJsonPath('available_room_types.0.available_rooms', 1)
            ->assertJsonPath('available_room_types.0.estimated_total_per_room_vnd', 990000);

        // Must never leak physical room numbers or customer PII
        $this->assertStringNotContainsString('DLX-101', $searchRes->getContent());
        $this->assertStringNotContainsString('DLX-102', $searchRes->getContent());
        $this->assertStringNotContainsString('hold-guest@example.com', $searchRes->getContent());

        // After 10 minutes, the unpaid hold expires and both rooms become available
        $this->travel(Booking::PAYMENT_HOLD_MINUTES + 1)->minutes();

        $afterExpiryRes = $this->postJson('/api/chatbot/tools/rooms/search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 2,
            'children' => 0,
            'room_count' => 1,
        ], [
            'Authorization' => 'Bearer tool-secret-999',
        ])->assertOk();

        $afterExpiryRes->assertJsonPath('available_room_types.0.available_rooms', 2);
        $this->travelBack();
    }

    public function test_knowledge_index_command_uses_gemini_embeddings_and_matches_ten_minute_hold_policy(): void
    {
        config([
            'services.gemini.api_key' => 'gemini-test-key',
            'services.gemini.embedding_model' => 'text-embedding-004',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'embedding' => ['values' => [0.25, 0.75, 0.5]],
            ], 200),
        ]);

        $this->artisan('knowledge:index')->assertSuccessful();

        $this->assertGreaterThan(0, KnowledgeChunk::where('embedding_model', 'text-embedding-004')->count());

        $holdChunks = KnowledgeChunk::where('content', 'like', '%10 phút%')->get();
        $this->assertNotEmpty($holdChunks, 'Approved knowledge chunks must document the 10-minute payment hold.');
    }

    public function test_three_login_portals_enforce_role_boundaries_and_isolate_sessions(): void
    {
        $customer = User::create([
            'username' => 'customer_portal',
            'fullname' => 'Khách Lưu Trú',
            'email' => 'customer-portal@example.com',
            'phone' => '0901112223',
            'password' => Hash::make('CustomerPass123!'),
            'role' => 'customer',
            'verified' => true,
        ]);

        $staff = User::create([
            'username' => 'staff_portal',
            'fullname' => 'Nhân Viên Lễ Tân',
            'email' => 'staff-portal@example.com',
            'phone' => '0902223334',
            'password' => Hash::make('StaffPass123!'),
            'role' => 'receptionist',
            'verified' => true,
        ]);

        $admin = User::create([
            'username' => 'admin_portal',
            'fullname' => 'Quản Trị Viên',
            'email' => 'admin-portal@example.com',
            'phone' => '0903334445',
            'password' => Hash::make('AdminPass123!'),
            'role' => 'admin',
            'verified' => true,
        ]);

        // 1. Staff login rejects admin and customer
        $this->from(route('staff.login'))->post(route('staff.login.submit'), [
            'username' => $admin->username,
            'password' => 'AdminPass123!',
        ])->assertRedirect(route('staff.login'))->assertSessionHasErrors('username');

        $this->from(route('staff.login'))->post(route('staff.login.submit'), [
            'username' => $customer->username,
            'password' => 'CustomerPass123!',
        ])->assertRedirect(route('staff.login'))->assertSessionHasErrors('username');

        // 2. Admin login rejects receptionist and customer
        $this->from(route('admin.login'))->post(route('admin.login.submit'), [
            'username' => $staff->username,
            'password' => 'StaffPass123!',
        ])->assertRedirect(route('admin.login'))->assertSessionHasErrors('username');

        // 3. Customer login accepts customer, and Staff login accepts receptionist in the SAME browser session
        $this->post(route('login'), [
            'email' => $customer->email,
            'password' => 'CustomerPass123!',
        ])->assertRedirect(route('home'));

        $this->post(route('staff.login.submit'), [
            'username' => $staff->username,
            'password' => 'StaffPass123!',
        ])->assertRedirect(route('staff.bookings'));

        // Both session contexts coexist without overwriting each other
        $this->assertSame($customer->id, session('customer_user_id'));
        $this->assertSame($staff->id, session('staff_user_id'));

        // Customer route resolves Auth::id() as customer
        $this->get(route('account.show'))
            ->assertOk()
            ->assertSee('Khách Lưu Trú');
        $this->assertSame($customer->id, auth()->id());

        // Staff route (/staff/profile and /staff/iot Face ID) resolves Auth::id() as staff
        $this->get(route('staff.profile'))
            ->assertOk()
            ->assertSee('Nhân Viên Lễ Tân');
        $this->assertSame($staff->id, auth()->id());

        $this->get(route('staff.iot'))
            ->assertOk()
            ->assertSee('Nhận diện khách lưu trú');

        // Logging out of customer portal preserves the active staff session
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertNull(session('customer_user_id'));
        $this->assertSame($staff->id, session('staff_user_id'));

        $this->get(route('staff.profile'))->assertOk()->assertSee('Nhân Viên Lễ Tân');

        // Logging out of staff portal clears staff session
        $this->post(route('staff.logout'))->assertRedirect(route('staff.login'));
        $this->assertNull(session('staff_user_id'));
    }
}
