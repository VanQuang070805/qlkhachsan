<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Mail\AccountUpdatedMail;
use App\Models\Booking;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Chatbot\HotelRoomToolService;
use App\Services\Chatbot\LangGraphChatbotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use ReflectionMethod;
use Tests\TestCase;

class RosalizaUnifiedRemediationTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalogRoom(float $price = 1000000): Room
    {
        $type = RoomType::create([
            'type_name' => 'Phòng Đôi Tiêu Chuẩn',
            'price' => $price,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
            'description' => 'Phòng nghỉ hiện đại tại trung tâm Hoàn Kiếm.',
            'image' => 'images/rooms/1.jpg',
        ]);

        return Room::create([
            'room_number' => '201',
            'room_type_id' => $type->id,
            'floor' => 2,
            'status' => 'available',
        ]);
    }

    public function test_brand_and_policy_copy_are_unified_as_rosaliza_hotel(): void
    {
        $this->seedCatalogRoom();

        foreach (['/', '/rooms', '/contact', '/login', '/register'] as $uri) {
            $response = $this->get($uri);
            $response->assertOk();
            $response->assertSee('Rosaliza Hotel');
            $response->assertDontSee('Posh Boutique');
            $response->assertDontSee('Rosa Hotel');
            $response->assertDontSee('Cầu Giấy');
        }

        $this->get('/')->assertSee('14:00 đến trước 18:00');
        $this->get('/rooms')->assertSee('14:00 - trước 18:00');
        $this->get('/contact')->assertSee('5B Trần Quốc Toản');
    }

    public function test_portals_10800_10801_10802_isolate_sessions_and_routes(): void
    {
        $this->seedCatalogRoom();

        $customerHome = $this->get('http://127.0.0.1:10800/');
        $customerHome->assertOk();
        $this->assertTrue(
            collect($customerHome->headers->getCookies())
                ->contains(fn ($cookie) => $cookie->getName() === 'rosaliza_customer_session')
        );

        // Staff route on customer port 10800 must be strictly blocked
        $this->get('http://127.0.0.1:10800/staff/login')->assertForbidden();

        $staffLogin = $this->get('http://127.0.0.1:10801/staff/login');
        $staffLogin->assertOk()->assertSee('Cổng Nhân Viên');
        $this->assertTrue(
            collect($staffLogin->headers->getCookies())
                ->contains(fn ($cookie) => $cookie->getName() === 'rosaliza_staff_session')
        );

        $adminLogin = $this->get('http://127.0.0.1:10802/admin/login');
        $adminLogin->assertOk()->assertSee('Cổng Quản Trị');
        $this->assertTrue(
            collect($adminLogin->headers->getCookies())
                ->contains(fn ($cookie) => $cookie->getName() === 'rosaliza_admin_session')
        );
    }

    public function test_customer_deposit_enforces_online_payment_only_across_web_and_api(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 20, 10, 0, 0, 'Asia/Ho_Chi_Minh'));
        $room = $this->seedCatalogRoom(1200000);

        $user = User::create([
            'username' => 'minhanh_customer',
            'fullname' => 'Nguyễn Minh Anh',
            'email' => 'minhanh@example.com',
            'phone' => '0912345678',
            'password' => bcrypt('Secret12345'),
            'role' => 'customer',
            'verified' => true,
        ]);

        $booking = Booking::create([
            'user_id' => $user->id,
            'customer_name' => $user->fullname,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'check_in' => '2026-11-22',
            'check_out' => '2026-11-23',
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 1200000,
            'deposit_amount' => 600000,
            'payment_method' => 'cash',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);
        $booking->rooms()->attach($room->id);

        // Web payment update rejects cash
        $this->withSession([
            'user_id' => $user->id,
            'customer_user_id' => $user->id,
        ])->patch(route('payment.update', $booking->id), [
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('payment_method');

        // Direct GET to payment.show with legacy cash pending booking redirects back to payment.form
        $this->withSession([
            'user_id' => $user->id,
            'customer_user_id' => $user->id,
        ])->get(route('payment.show', $booking->id))
            ->assertRedirect(route('payment.form', $booking->id));

        // API v1 rejects cash deposit for customer booking
        $apiResponse = $this->withSession(['customer_user_id' => $user->id])
            ->withHeader('Idempotency-Key', 'idem-cash-reject-001')
            ->postJson('/api/v1/customer/bookings', [
                'room_type_id' => $room->room_type_id,
                'check_in' => '2026-11-25',
                'check_out' => '2026-11-26',
                'adult_count' => 2,
                'child_count' => 0,
                'room_count' => 1,
                'payment_method' => 'cash',
            ])->assertStatus(422);
        $this->assertNotEmpty(
            data_get($apiResponse->json(), 'error.fields.payment_method')
            ?? data_get($apiResponse->json(), 'errors.payment_method')
        );

        Carbon::setTestNow();
    }

    public function test_checkin_window_allows_before_1800_and_autocancels_at_1800(): void
    {
        $room = $this->seedCatalogRoom(800000);
        $today = '2026-11-20';
        $tomorrow = '2026-11-21';

        $booking = Booking::create([
            'customer_name' => 'Trần Quốc Bảo',
            'customer_email' => 'quocbao@example.com',
            'customer_phone' => '0987654321',
            'check_in' => $today,
            'check_out' => $tomorrow,
            'adult_count' => 1,
            'child_count' => 0,
            'total_price' => 800000,
            'deposit_amount' => 400000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
        $booking->rooms()->attach($room->id);

        // At 17:45, booking is still active and same-day search is permitted
        Carbon::setTestNow(Carbon::create(2026, 11, 20, 17, 45, 0, 'Asia/Ho_Chi_Minh'));
        $this->artisan('bookings:expire-no-shows')->assertExitCode(0);
        $this->assertSame('confirmed', $booking->fresh()->status);

        $toolResultBefore = app(HotelRoomToolService::class)->searchPublicRooms([
            'check_in' => $today,
            'check_out' => $tomorrow,
            'guests' => 1,
            'rooms' => 1,
        ]);
        $this->assertArrayNotHasKey('error', $toolResultBefore);

        // At 18:00 sharp, same-day search is blocked and unchecked confirmed booking is auto-cancelled
        Carbon::setTestNow(Carbon::create(2026, 11, 20, 18, 0, 0, 'Asia/Ho_Chi_Minh'));
        $toolResultAfter = app(HotelRoomToolService::class)->searchPublicRooms([
            'check_in' => $today,
            'check_out' => $tomorrow,
            'guests' => 1,
            'rooms' => 1,
        ]);
        $this->assertArrayHasKey('error', $toolResultAfter);
        $this->assertStringContainsString('18:00', $toolResultAfter['error']);

        $this->artisan('bookings:expire-no-shows')->assertExitCode(0);
        $fresh = $booking->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertSame('Không đến nhận phòng trước 18:00.', $fresh->cancellation_reason);

        Carbon::setTestNow();
    }

    public function test_transactional_emails_match_rosaliza_brand_and_live_database_values(): void
    {
        $room = $this->seedCatalogRoom(1500000);
        $booking = Booking::create([
            'customer_name' => 'Lê Thu Hà',
            'customer_email' => 'thuha@example.com',
            'customer_phone' => '0901122334',
            'check_in' => '2026-12-10',
            'check_out' => '2026-12-12',
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 3000000,
            'deposit_amount' => 1500000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
        $booking->rooms()->attach($room->id);
        $booking->load('rooms.roomType');

        $controller = app(PaymentController::class);
        $method = new ReflectionMethod(PaymentController::class, 'buildPaymentEmailHtml');
        $method->setAccessible(true);
        $html = $method->invoke($controller, $booking, $booking->rooms, 'VietQR', 'TXN12345', 'Rosaliza Hotel', true, true);

        $this->assertStringContainsString('Rosaliza Hotel', $html);
        $this->assertStringContainsString('#'.$booking->id, $html);
        $this->assertStringContainsString('3.000.000', $html);
        $this->assertStringContainsString('1.500.000', $html);
        $this->assertStringContainsString('14:00 - trước 18:00', $html);
        $this->assertStringContainsString('Sau 18:00', $html);
        $this->assertStringContainsString('cid:checkin-qr', $html);
        $this->assertStringNotContainsString('Posh Boutique', $html);
        $this->assertStringNotContainsString('—', $html);

        // Staff role assignment email renders assigned Role name instead of 'Khách hàng'
        $role = Role::create([
            'name' => 'Trưởng ca lễ tân',
            'slug' => 'shift_lead',
            'permissions' => Role::DEFAULT_RECEPTIONIST_PERMISSIONS,
        ]);
        $staff = User::create([
            'username' => 'lead01',
            'fullname' => 'Phạm Hải Đăng',
            'email' => 'haidang@rosalizahotel.vn',
            'password' => bcrypt('Secret12345'),
            'role' => 'receptionist',
            'role_id' => $role->id,
            'verified' => true,
        ]);

        $accountUpdatedHtml = (new AccountUpdatedMail(
            $staff->fullname,
            $staff->username,
            $staff->email,
            $staff->phone,
            $role->name
        ))->render();

        $this->assertStringContainsString('Rosaliza Hotel', $accountUpdatedHtml);
        $this->assertStringContainsString('Trưởng ca lễ tân', $accountUpdatedHtml);
        $this->assertStringNotContainsString('Khách hàng', $accountUpdatedHtml);
    }

    public function test_chatbot_uses_fast_default_model_and_streams_cached_answers_without_delay(): void
    {
        config([
            'services.langgraph.enabled' => true,
            'services.langgraph.base_url' => 'http://127.0.0.1:2024',
        ]);

        $service = app(LangGraphChatbotService::class);
        $ref = new ReflectionMethod(LangGraphChatbotService::class, 'resolvedQueryModel');
        $ref->setAccessible(true);
        $this->assertStringContainsString('flash-lite', $ref->invoke($service));

        $question = 'Quy định nhận phòng tại Rosaliza Hotel như thế nào?';
        $fingerprint = $service->buildFingerprint($question, []);
        Cache::put("royal:chatbot:answer:{$fingerprint}", [
            'reply' => 'Giờ nhận phòng tiêu chuẩn từ 14:00 đến trước 18:00, quá 18:00 đơn sẽ tự động hủy.',
            'sources' => ['rosaliza-operations'],
        ], 300);

        $response = $this->postJson(route('chatbot.stream'), [
            'message' => $question,
            'history' => [],
        ], ['Accept' => 'text/event-stream']);

        $response->assertOk();
        $streamed = $response->streamedContent();
        $this->assertStringContainsString('14:00', $streamed);
        $this->assertStringContainsString('18:00', $streamed);
        $this->assertStringContainsString('"done":true', $streamed);
    }

    public function test_google_oauth_supports_port_10800_registration_account_linking_and_booking_phone_sync(): void
    {
        $room = $this->seedCatalogRoom(1200000);
        config([
            'services.google.client_id' => 'google-client-id-demo',
            'services.google.client_secret' => 'google-client-secret-demo',
        ]);

        // 1. Port 10800 dynamically sets redirect_uri to http://localhost:10800/auth/google/callback
        $redirectRes = $this->get('http://localhost:10800/auth/google');
        $redirectRes->assertStatus(302);
        $this->assertSame('http://localhost:10800/auth/google/callback', config('services.google.redirect'));

        // 2. Brand-new Google user receives 6-digit OTP via Gmail, verifies at /verify, logs in, and syncs phone on first booking
        config(['mail.default' => 'array']);
        app('mailer')->getSymfonyTransport()->flush();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-new-9001',
            'name' => 'Trần Minh Ngọc',
            'email' => 'minhngoc.google@example.com',
            'avatar' => 'https://example.com/ngoc-avatar.jpg',
            'email_verified' => true,
        ]));

        $this->get('http://localhost:10800/auth/google/callback')
            ->assertRedirect(route('verify'))
            ->assertSessionHas('pending_verify_email', 'minhngoc.google@example.com');

        $googleCustomer = User::where('email', 'minhngoc.google@example.com')->firstOrFail();
        $this->assertSame('google-new-9001', $googleCustomer->google_id);
        $this->assertFalse((bool) $googleCustomer->verified);
        $this->assertNotNull($googleCustomer->otp_code);

        $sentMessages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertNotEmpty($sentMessages);
        preg_match('/\b(\d{6})\b/', (string) $sentMessages[0]->getOriginalMessage()->getTextBody(), $otpMatch);
        $this->assertNotEmpty($otpMatch[1] ?? null);

        $this->withSession(['pending_verify_email' => 'minhngoc.google@example.com'])
            ->post('http://localhost:10800/verify', ['otp' => $otpMatch[1]])
            ->assertRedirect(route('home'));

        $googleCustomer->refresh();
        $this->assertTrue((bool) $googleCustomer->verified);
        $this->assertNull($googleCustomer->otp_code);
        $this->assertNull($googleCustomer->phone);

        $checkIn = Carbon::now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString();
        $checkOut = Carbon::now('Asia/Ho_Chi_Minh')->addDays(5)->toDateString();

        $this->actingAs($googleCustomer)->withSession([
            'customer_user_id' => $googleCustomer->id,
            'customer_user' => ['id' => $googleCustomer->id, 'role' => 'customer', 'verified' => true],
        ])->postJson('/api/v1/customer/bookings', [
            'room_type_id' => $room->room_type_id,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adult_count' => 2,
            'child_count' => 0,
            'customer_phone' => '0987654321',
            'payment_method' => 'vietqr',
        ])->assertCreated();

        $this->assertSame('0987654321', $googleCustomer->fresh()->phone);

        // 3. Existing password customer links Google account from /account?intent=link
        $passwordCustomer = User::create([
            'username' => 'khachmatkhau',
            'fullname' => 'Lê Bảo Châu',
            'email' => 'baochau@example.com',
            'phone' => '0912345678',
            'password' => bcrypt('SecretPass123'),
            'role' => 'customer',
            'verified' => true,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-linked-777',
            'name' => 'Lê Bảo Châu',
            'email' => 'baochau@example.com',
            'avatar' => 'https://example.com/chau.jpg',
            'email_verified' => true,
        ]));

        $this->actingAs($passwordCustomer)->withSession([
            'customer_user_id' => $passwordCustomer->id,
            'customer_user' => ['id' => $passwordCustomer->id, 'email' => $passwordCustomer->email, 'role' => 'customer', 'verified' => true],
        ])->get('http://localhost:10800/auth/google?intent=link')->assertStatus(302);

        $this->actingAs($passwordCustomer)->withSession([
            'customer_user_id' => $passwordCustomer->id,
            'customer_user' => ['id' => $passwordCustomer->id, 'email' => $passwordCustomer->email, 'role' => 'customer', 'verified' => true],
            'google_oauth_link_user_id' => $passwordCustomer->id,
        ])->get('http://localhost:10800/auth/google/callback')
            ->assertRedirect(route('account.show'))
            ->assertSessionHas('success');

        $this->assertSame('google-linked-777', $passwordCustomer->fresh()->google_id);
    }

    public function test_registration_validates_real_gmail_rules_and_renders_otp_html_without_closure_error(): void
    {
        config(['mail.default' => 'array']);

        // 1. Rejects typo domain @gmial.com
        $this->postJson(route('register'), [
            'name' => 'Nguyễn Văn Thử',
            'email' => 'nguyenvanthu123@gmial.com',
            'phone' => '0901234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);

        // 2. Rejects Gmail username shorter than 6 characters
        $this->postJson(route('register'), [
            'name' => 'Nguyễn Văn Thử',
            'email' => 'abc@gmail.com',
            'phone' => '0901234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);

        // 3. Rejects disposable email domain
        $this->postJson(route('register'), [
            'name' => 'Nguyễn Văn Thử',
            'email' => 'guest999@mailinator.com',
            'phone' => '0901234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);

        // 4. Valid registration executes the real Mail::raw closure and renders emails.otp HTML cleanly
        $this->postJson(route('register'), [
            'name' => 'Phạm Thu Hà',
            'email' => 'phamthuha2026@example.com',
            'phone' => '0901234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertOk()->assertJsonPath('success', true);

        $sentMessages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $sentMessages);
        $htmlBody = (string) $sentMessages[0]->getOriginalMessage()->getHtmlBody();
        $this->assertStringContainsString('Rosaliza Hotel', $htmlBody);
        $this->assertStringContainsString('Phạm Thu Hà', $htmlBody);
        $this->assertMatchesRegularExpression('/\b\d{6}\b/', $htmlBody);
    }

    public function test_eleven_ui_ux_and_chatbot_user_reality_requirements(): void
    {
        $roomA = $this->seedCatalogRoom(1100000);
        $otherType = RoomType::create([
            'type_name' => 'Presidential Panorama',
            'price' => 3200000,
            'max_adults' => 4,
            'max_children' => 2,
            'max_guests' => 6,
            'description' => 'Không gian sang trọng tầng cao.',
            'image' => 'images/rooms/2.jpg',
        ]);
        $roomB = Room::create([
            'room_number' => '808',
            'floor' => 8,
            'room_type_id' => $otherType->id,
            'status' => 'available',
        ]);

        $customer = User::create([
            'username' => 'khach11muc',
            'fullname' => 'Hoàng Minh Khôi',
            'email' => 'minhkhoi@example.com',
            'phone' => '0911223344',
            'password' => bcrypt('SecretPass123!'),
            'role' => 'customer',
            'verified' => true,
        ]);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'customer_name' => $customer->fullname,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'check_in' => Carbon::now('Asia/Ho_Chi_Minh')->addDays(4)->toDateString(),
            'check_out' => Carbon::now('Asia/Ho_Chi_Minh')->addDays(6)->toDateString(),
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 2200000,
            'deposit_amount' => 1100000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
        $booking->rooms()->attach($roomA->id);

        $customerSession = [
            'customer_user_id' => $customer->id,
            'customer_user' => [
                'id' => $customer->id,
                'fullname' => $customer->fullname,
                'email' => $customer->email,
                'role' => 'customer',
                'verified' => true,
            ],
            'user_id' => $customer->id,
        ];

        // Item 1: /my-bookings renders clickable #ID, detail modal, room_type filter, and valid JS
        $myBookingsRes = $this->actingAs($customer)
            ->withSession($customerSession)
            ->get(route('booking.mine', ['room_type' => $roomA->room_type_id]));
        $myBookingsRes->assertOk()
            ->assertSee('Mã đơn #'.$booking->id)
            ->assertSee('bookingDetailModal', false)
            ->assertSee('bookingRoomTypeSelect', false)
            ->assertDontSee('function openTrợ lýChat', false);

        // Filtering by a room_type the customer did NOT book returns empty list cleanly
        $filteredEmpty = $this->actingAs($customer)
            ->withSession($customerSession)
            ->get(route('booking.mine', ['room_type' => $otherType->id]));
        $filteredEmpty->assertOk()->assertDontSee('Mã đơn #'.$booking->id);

        // Item 2: Enriched Check-in QR payload contains room info AND passes staff scan endpoint
        $qrPayload = app(\App\Services\CheckInTokenService::class)->qrPayload($booking);
        $this->assertStringContainsString('HOTEL:Rosaliza Hotel', $qrPayload);
        $this->assertStringContainsString('BOOKING:#'.$booking->id, $qrPayload);
        $this->assertStringContainsString('ROOMS:'.$roomA->room_number, $qrPayload);

        $staff = User::create([
            'username' => 'letan_qr',
            'fullname' => 'Lễ Tân QR',
            'email' => 'letanqr@rosalizahotel.vn',
            'password' => bcrypt('SecretPass123!'),
            'role' => 'receptionist',
            'verified' => true,
        ]);
        $staffSession = [
            'staff_user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ];
        $this->actingAs($staff)->withSession($staffSession)
            ->getJson(route('staff.reception.booking-by-scan', ['payload' => $qrPayload]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('booking.id', $booking->id);

        // Item 3: Cancel page removes the Rosaliza Hotel badge pill inside card bar
        $cancelRes = $this->actingAs($customer)
            ->withSession($customerSession)
            ->get(route('booking.cancel.show', $booking->id));
        $cancelRes->assertOk()
            ->assertSee('Chi tiết đặt phòng #'.$booking->id, false)
            ->assertDontSee('<span class="badge rounded-pill bg-slate-100 text-slate-700 border border-slate-200" style="font-size: 10.5px; font-weight: 600;">Rosaliza Hotel</span>', false);

        // Item 6: /account uses official 4-color Google SVG & staff/bookings has crisp QR scan SVG
        $this->actingAs($customer)->withSession($customerSession)
            ->get(route('account.show'))
            ->assertOk()
            ->assertSee('#4285F4', false)
            ->assertSee('#34A853', false)
            ->assertDontSee('bi-google', false);

        // Item 9: Redesigned /contact has cohesive Vietnamese copy and no disabled dead form
        $this->get(route('contact'))
            ->assertOk()
            ->assertSee('Chốn dừng chân tĩnh lặng giữa lòng phố cổ Hà Nội')
            ->assertSee('Trò chuyện cùng Pico AI')
            ->assertDontSee('<fieldset disabled', false)
            ->assertDontSee('data-rb-split', false);

        // Item 11: Room detail page has only one primary booking CTA and no mobile duplicate .app-bottom-dock
        $this->get(route('rooms.detail', $roomA->room_type_id))
            ->assertOk()
            ->assertSee('id="btnBook"', false)
            ->assertDontSee('app-bottom-dock', false)
            ->assertDontSee('mobileDockRate', false);

        // Item 8: Chatbot user reality & reset endpoint
        $langGraph = app(LangGraphChatbotService::class);
        $realityReq = \Illuminate\Http\Request::create('/chatbot/stream', 'POST');
        $realityReq->setLaravelSession(app('session.store'));
        $realityReq->session()->put($customerSession);

        $realitySummary = $langGraph->buildUserRealitySummary($realityReq, [
            'path' => '/rooms/'.$roomB->room_type_id,
            'title' => 'Presidential Panorama',
            'room_name' => 'Presidential Panorama',
            'room_price' => '3.200.000đ',
        ]);
        $this->assertStringContainsString('Hoàng Minh Khôi', $realitySummary);
        $this->assertStringContainsString('Đơn #'.$booking->id, $realitySummary);
        $this->assertStringContainsString('Presidential Panorama', $realitySummary);

        $this->actingAs($customer)->withSession($customerSession)
            ->postJson(route('chatbot.reset'))
            ->assertOk()
            ->assertJsonPath('reset', true);
    }

    public function test_admin_cannot_edit_or_change_customer_password_but_can_lock_customer_and_fully_edit_staff(): void
    {
        \App\Models\Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Quản trị viên']);
        \App\Models\Role::firstOrCreate(['slug' => 'receptionist'], ['name' => 'Lễ tân']);
        \App\Models\Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Khách hàng']);

        $admin = User::create([
            'username' => 'admin_rbac_guard',
            'fullname' => 'Quản Trị Viên Rosaliza',
            'email' => 'admin.rbac.guard@rosalizahotel.vn',
            'phone' => '0901112222',
            'password' => bcrypt('Admin@2026'),
            'role' => 'admin',
            'verified' => true,
        ]);

        $customer = User::create([
            'username' => 'customer_protected',
            'fullname' => 'Khách Hàng Bảo Vệ',
            'email' => 'customer.protected@gmail.com',
            'phone' => '0903334444',
            'password' => bcrypt('CustomerSecret@2026'),
            'role' => 'customer',
            'verified' => true,
        ]);
        $originalPasswordHash = $customer->password;

        $staff = User::create([
            'username' => 'staff_editable',
            'fullname' => 'Lễ Tân Vận Hành',
            'email' => 'staff.editable@rosalizahotel.vn',
            'phone' => '0905556666',
            'password' => bcrypt('StaffInit@2026'),
            'role' => 'receptionist',
            'verified' => true,
        ]);

        $adminSession = [
            'staff_user_id' => $admin->id,
            'staff_user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
            'user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
        ];

        // 1. User index shows edit link for staff, NEVER for customer; shows lock toggle for customer
        $indexRes = $this->actingAs($admin)->withSession($adminSession)
            ->get(route('admin.users.index'));
        $indexRes->assertOk()
            ->assertSee(route('admin.users.edit', $staff), false)
            ->assertDontSee(route('admin.users.edit', $customer), false)
            ->assertSee('class="command-search-bar"', false);

        // 2. Direct GET /admin/users/{customer}/edit is rejected
        $this->actingAs($admin)->withSession($adminSession)
            ->get(route('admin.users.edit', $customer))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        // 3. Direct PUT /admin/users/{customer} (profile or password tampering) is strictly blocked
        $this->actingAs($admin)->withSession($adminSession)
            ->put(route('admin.users.update', $customer), [
                'username' => 'tampered_customer',
                'fullname' => 'Tên Bị Sửa Trái Phép',
                'email' => 'tampered@gmail.com',
                'phone' => '0999999999',
                'password' => 'HackedPass123!',
                'password_confirmation' => 'HackedPass123!',
                'role' => 'customer',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $customer->refresh();
        $this->assertSame('Khách Hàng Bảo Vệ', $customer->fullname);
        $this->assertSame('customer.protected@gmail.com', $customer->email);
        $this->assertSame($originalPasswordHash, $customer->password);

        // JSON/AJAX PUT on customer returns 403 Forbidden
        $this->actingAs($admin)->withSession($adminSession)
            ->putJson(route('admin.users.update', $customer), [
                'fullname' => 'Tampered JSON',
                'password' => 'HackedPass123!',
                'password_confirmation' => 'HackedPass123!',
            ])
            ->assertForbidden();

        // 4. Admin CAN lock and unlock customer account status
        $this->actingAs($admin)->withSession($adminSession)
            ->patchJson(route('admin.users.toggle-verified', $customer))
            ->assertOk()
            ->assertJsonPath('verified', false);
        $this->assertFalse((bool) $customer->fresh()->verified);

        $this->actingAs($admin)->withSession($adminSession)
            ->patchJson(route('admin.users.toggle-verified', $customer))
            ->assertOk()
            ->assertJsonPath('verified', true);
        $this->assertTrue((bool) $customer->fresh()->verified);

        // 5. Admin retains full edit & password change permission on staff accounts
        $this->actingAs($admin)->withSession($adminSession)
            ->get(route('admin.users.edit', $staff))
            ->assertOk();

        $this->actingAs($admin)->withSession($adminSession)
            ->put(route('admin.users.update', $staff), [
                'username' => 'staff_editable_v2',
                'fullname' => 'Lễ Tân Trưởng Ca',
                'email' => 'staff.updated@rosalizahotel.vn',
                'phone' => '0907778888',
                'password' => 'NewStaffPass2026',
                'password_confirmation' => 'NewStaffPass2026',
                'role' => 'receptionist',
            ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $staff->refresh();
        $this->assertSame('Lễ Tân Trưởng Ca', $staff->fullname);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('NewStaffPass2026', $staff->password));
    }

    public function test_staff_can_lookup_and_quick_checkin_via_manual_booking_id(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-22 15:00:00', 'Asia/Ho_Chi_Minh'));
        try {
            $room = $this->seedCatalogRoom(1500000);
            $customer = User::create([
                'username' => 'guest_manual_id',
                'fullname' => 'Đặng Thu Hà',
                'email' => 'thuha.manual@gmail.com',
                'phone' => '0918222333',
                'password' => bcrypt('GuestPass@2026'),
                'role' => 'customer',
                'verified' => true,
            ]);

            $booking = Booking::create([
                'user_id' => $customer->id,
                'customer_name' => $customer->fullname,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'check_in' => '2026-11-22',
                'check_out' => '2026-11-24',
                'adult_count' => 2,
                'child_count' => 0,
                'total_price' => 3000000,
                'deposit_amount' => 1500000,
                'paid_amount' => 1500000,
                'payment_method' => 'vietqr',
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);
            $booking->rooms()->attach($room->id);

            $staff = User::create([
                'username' => 'reception_manual_id',
                'fullname' => 'Lễ Tân Trực Quầy',
                'email' => 'reception.manual@rosalizahotel.vn',
                'phone' => '0918999888',
                'password' => bcrypt('Staff@2026'),
                'role' => 'receptionist',
                'verified' => true,
            ]);

            $staffSession = [
                'staff_user_id' => $staff->id,
                'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
            ];

            $lookupRes = $this->actingAs($staff)->withSession($staffSession)
                ->getJson(route('staff.reception.booking-by-scan', ['booking_id' => '#'.$booking->id]));
            $lookupRes->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('booking.id', $booking->id)
                ->assertJsonPath('booking.customer_name', 'Đặng Thu Hà');

            $checkinRes = $this->actingAs($staff)->withSession($staffSession)
                ->postJson(route('staff.reception.quick-checkin'), ['booking_id' => (string) $booking->id]);
            $checkinRes->assertOk()
                ->assertJsonPath('success', true);

            $this->assertSame('checked_in', $booking->fresh()->status);
            $this->assertSame('occupied', $room->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_cancellation_refund_hourly_daily_extension_holiday_pricing_and_checkin_checkout_flows(): void
    {
        $admin = User::create([
            'username' => 'admin_ops_flow',
            'fullname' => 'Quản Trị Vận Hành',
            'email' => 'admin.ops@rosalizahotel.vn',
            'phone' => '0901000111',
            'password' => bcrypt('Admin@2026'),
            'role' => 'admin',
            'verified' => true,
        ]);
        $staff = User::create([
            'username' => 'staff_ops_flow',
            'fullname' => 'Lễ Tân Vận Hành',
            'email' => 'staff.ops@rosalizahotel.vn',
            'phone' => '0901000222',
            'password' => bcrypt('Staff@2026'),
            'role' => 'receptionist',
            'verified' => true,
        ]);
        $customer = User::create([
            'username' => 'guest_ops_flow',
            'fullname' => 'Phạm Gia Huy',
            'email' => 'giahuy.ops@gmail.com',
            'phone' => '0901000333',
            'password' => bcrypt('GuestPass@2026'),
            'role' => 'customer',
            'verified' => true,
        ]);

        $adminSession = [
            'staff_user_id' => $admin->id,
            'staff_user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
            'user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
        ];
        $staffSession = [
            'staff_user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ];
        $customerSession = [
            'customer_user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
        ];

        $room = $this->seedCatalogRoom(1000000); // 1.000.000đ / đêm

        try {
            Carbon::setTestNow(Carbon::parse('2026-11-20 10:00:00', 'Asia/Ho_Chi_Minh'));

            // 1. Luồng sửa giá tiền theo dịp lễ (Admin tạo +20% cho 01/12/2026 -> 02/12/2026)
            $this->actingAs($admin)->withSession($adminSession)
                ->post(route('admin.price-settings.store'), [
                    'name' => 'Lễ Hội Mùa Đông',
                    'start_date' => '2026-12-01',
                    'end_date' => '2026-12-02',
                    'adjustment_type' => 'percent',
                    'adjustment_value' => 20,
                    'status' => '1',
                ])
                ->assertRedirect(route('admin.price-settings.index'))
                ->assertSessionHas('success');

            // Chặn trùng lặp lịch điều chỉnh giá đang bật
            $this->actingAs($admin)->withSession($adminSession)
                ->post(route('admin.price-settings.store'), [
                    'name' => 'Lễ Hội Trùng Ngày',
                    'start_date' => '2026-12-02',
                    'end_date' => '2026-12-03',
                    'adjustment_type' => 'fixed',
                    'adjustment_value' => 300000,
                    'status' => '1',
                ])
                ->assertSessionHasErrors(['start_date']);

            // Đồng bộ giá lễ sang chi tiết phòng JSON & đặt phòng (2 đêm 01/12 -> 03/12 = 1.200.000 * 2 = 2.400.000đ)
            $detailJson = $this->getJson(route('rooms.detail', [
                'id' => $room->room_type_id,
                'check_in' => '2026-12-01',
                'check_out' => '2026-12-03',
            ]));
            $detailJson->assertOk()->assertJsonPath('nightly_price', 1200000);

            $holidayBooking = Booking::create([
                'user_id' => $customer->id,
                'customer_name' => $customer->fullname,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'check_in' => '2026-12-01',
                'check_out' => '2026-12-03',
                'adult_count' => 2,
                'child_count' => 0,
                'total_price' => 2400000,
                'deposit_amount' => 1200000,
                'payment_method' => 'vietqr',
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);
            $holidayBooking->rooms()->attach($room->id);
            \App\Models\PaymentLog::create([
                'booking_id' => $holidayBooking->id,
                'gateway' => 'vietqr',
                'transaction_id' => 'DEP-HOLIDAY-01',
                'amount' => 1200000,
                'purpose' => 'deposit',
                'status' => 'success',
            ]);

            // Ngày nhận 01/12 nằm trong dịp lễ PriceSetting nên áp dụng mốc hủy trước 3 ngày
            $this->assertSame(3, $holidayBooking->refundDeadlineDays());
            $this->assertTrue($holidayBooking->isRefundEligible());

            // 2. Luồng Hoàn / Hủy phòng: Khách hủy trước hạn -> Chờ hoàn 1.200.000đ -> Lễ tân xác nhận hoàn tiền
            $this->actingAs($customer)->withSession($customerSession)
                ->post(route('booking.cancel', $holidayBooking->id), [
                    'reason' => 'Thay đổi lịch công tác đột xuất',
                ])
                ->assertRedirect(route('booking.mine'))
                ->assertSessionHas('success');

            $holidayBooking->refresh();
            $this->assertSame('cancelled', $holidayBooking->status);
            $this->assertSame('eligible', $holidayBooking->refund_status);
            $this->assertEquals(1200000, (float) $holidayBooking->refund_amount);

            $this->actingAs($staff)->withSession($staffSession)
                ->patch(route('staff.bookings.refund', $holidayBooking->id), [
                    'provider_reference' => 'REF-VIETQR-9901',
                ])
                ->assertSessionHas('success');

            $holidayBooking->refresh();
            $this->assertSame('refunded', $holidayBooking->refund_status);
            $this->assertSame('refunded', $holidayBooking->payment_status);
            $this->assertDatabaseHas('refund_transactions', [
                'booking_id' => $holidayBooking->id,
                'amount' => 1200000,
                'provider_reference' => 'REF-VIETQR-9901',
                'status' => 'completed',
            ]);

            // 3. Luồng Nhận phòng -> Gia hạn theo giờ -> Gia hạn theo ngày -> Trả phòng kèm phụ thu quá giờ
            Carbon::setTestNow(Carbon::parse('2026-11-25 14:30:00', 'Asia/Ho_Chi_Minh'));
            $stayBooking = Booking::create([
                'user_id' => $customer->id,
                'customer_name' => $customer->fullname,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'check_in' => '2026-11-25',
                'check_out' => '2026-11-26',
                'adult_count' => 2,
                'child_count' => 0,
                'total_price' => 1000000,
                'deposit_amount' => 500000,
                'payment_method' => 'vietqr',
                'payment_status' => 'paid',
                'status' => 'confirmed',
            ]);
            $stayBooking->rooms()->attach($room->id);
            \App\Models\PaymentLog::create([
                'booking_id' => $stayBooking->id,
                'gateway' => 'vietqr',
                'transaction_id' => 'DEP-STAY-02',
                'amount' => 500000,
                'purpose' => 'deposit',
                'status' => 'success',
            ]);

            // 3A. Lễ tân Check-in phòng
            $this->actingAs($staff)->withSession($staffSession)
                ->postJson(route('staff.room.checkin', $room->id))
                ->assertOk()
                ->assertJsonPath('success', true);

            $this->assertSame('checked_in', $stayBooking->fresh()->status);
            $this->assertSame('occupied', $room->fresh()->status);

            // Khi đang checked_in, khách vào /my-bookings không còn thấy nút Hủy phòng nhưng có nút Gia hạn phòng
            $this->actingAs($customer)->withSession($customerSession)
                ->get(route('booking.mine'))
                ->assertOk()
                ->assertSee('Gia hạn phòng')
                ->assertDontSee(route('booking.cancel.show', $stayBooking->id), false);

            // 3B. Khách gia hạn theo giờ (+3 giờ vào ngày trả phòng 26/11: 12:00 -> 15:00, phí 3 * 10% = 300.000đ)
            Carbon::setTestNow(Carbon::parse('2026-11-26 10:30:00', 'Asia/Ho_Chi_Minh'));
            $this->actingAs($customer)->withSession($customerSession)
                ->post(route('booking.extend', $stayBooking), [
                    'booking_id' => $stayBooking->id,
                    'mode' => 'hours',
                    'amount' => 3,
                ])
                ->assertSessionHas('success');

            $stayBooking->refresh();
            $this->assertEquals(1300000, (float) $stayBooking->total_price);
            $this->assertSame('15:00 26/11/2026', $stayBooking->scheduledCheckoutAt()->format('H:i d/m/Y'));

            // 3C. Lễ tân gia hạn tiếp theo ngày (+1 ngày đến 27/11, khấu trừ 300.000đ giờ lẻ đã tính -> cộng thêm 700.000đ = 2.000.000đ)
            $this->actingAs($staff)->withSession($staffSession)
                ->postJson(route('staff.reception.extend'), [
                    'room_id' => $room->id,
                    'mode' => 'days',
                    'amount' => 1,
                ])
                ->assertOk()
                ->assertJsonPath('added_amount', 700000)
                ->assertJsonPath('total_price', 2000000)
                ->assertJsonPath('new_checkout', '2026-11-27');

            // 3D. Không được phép chuyển thẳng phòng occupied sang cleaning khi chưa thanh toán trả phòng
            $this->actingAs($staff)->withSession($staffSession)
                ->postJson(route('staff.reception.update-status'), [
                    'room_id' => $room->id,
                    'status' => 'cleaning',
                ])
                ->assertUnprocessable();

            // 3E. Khách trả phòng trễ lúc 14:15 ngày 27/11 (sau 13:00 -> phụ thu 50% của 1 đêm = 500.000đ)
            // Tổng phải thu còn lại = (2.000.000 - 500.000 cọc) + 500.000 phụ thu = 2.000.000đ
            Carbon::setTestNow(Carbon::parse('2026-11-27 14:15:00', 'Asia/Ho_Chi_Minh'));
            $this->actingAs($staff)->withSession($staffSession)
                ->postJson(route('staff.bookings.checkout-payment', $stayBooking->id), [
                    'payment_method' => 'cash',
                    'waive_late_fee' => false,
                ])
                ->assertOk()
                ->assertJsonPath('success', true);

            $stayBooking->refresh();
            $room->refresh();
            $this->assertSame('completed', $stayBooking->status);
            $this->assertEquals(500000, (float) $stayBooking->late_checkout_fee);
            $this->assertEquals(0, $stayBooking->outstandingAmount());
            $this->assertSame('cleaning', $room->status);

            // 3F. Sau khi dọn xong -> chuyển phòng từ cleaning về available
            $this->actingAs($staff)->withSession($staffSession)
                ->postJson(route('staff.reception.update-status'), [
                    'room_id' => $room->id,
                    'status' => 'available',
                ])
                ->assertOk()
                ->assertJsonPath('new_status', 'available');
            $this->assertSame('available', $room->fresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }
}
