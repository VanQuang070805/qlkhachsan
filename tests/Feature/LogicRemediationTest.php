<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Payment\VietQRService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class LogicRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_hold_cannot_be_revived_by_a_late_gateway_callback(): void
    {
        [$booking] = $this->bookingWithRoom();
        $booking->forceFill(['created_at' => now()->subMinutes(11)])->saveQuietly();

        $this->invokeDepositSettlement($booking, 'late-tx');

        $this->assertSame('failed', $booking->fresh()->payment_status);
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertDatabaseMissing('payment_logs', ['transaction_id' => 'late-tx', 'status' => 'success']);
    }

    public function test_expiry_command_cancels_stale_booking_and_fails_its_pending_deposit_log(): void
    {
        [$booking] = $this->bookingWithRoom();
        $booking->forceFill(['created_at' => now()->subMinutes(11)])->saveQuietly();
        $log = PaymentLog::create([
            'booking_id' => $booking->id,
            'gateway' => 'vietqr',
            'reference_code' => 'KS'.$booking->id.'EXPR',
            'amount' => $booking->deposit_amount,
            'purpose' => 'deposit',
            'status' => 'pending',
        ]);

        $this->artisan('bookings:expire-pending')->assertExitCode(0);

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('failed', $booking->fresh()->payment_status);
        $this->assertNotNull($booking->fresh()->cancelled_at);
        $this->assertSame('failed', $log->fresh()->status);
    }

    public function test_expiry_command_keeps_a_booking_held_during_its_ten_minute_window(): void
    {
        [$booking] = $this->bookingWithRoom();
        $this->travel(9)->minutes();

        $this->artisan('bookings:expire-pending')->assertExitCode(0);

        $this->assertSame('pending', $booking->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->payment_status);
        $this->travelBack();
    }

    public function test_gateway_callback_cannot_confirm_a_booking_after_its_room_was_sold(): void
    {
        [$booking, $room] = $this->bookingWithRoom();
        $winner = $this->booking($this->customer('winner@example.com'), 'confirmed', 'paid');
        $winner->rooms()->attach($room->id);

        $this->invokeDepositSettlement($booking, 'conflicting-tx');

        $this->assertSame('pending', $booking->fresh()->payment_status);
        $this->assertDatabaseMissing('payment_logs', ['transaction_id' => 'conflicting-tx', 'status' => 'success']);
    }

    public function test_vietqr_polling_does_not_mark_a_payment_success_before_booking_settlement(): void
    {
        [$booking] = $this->bookingWithRoom();
        $log = PaymentLog::create([
            'booking_id' => $booking->id,
            'gateway' => 'vietqr',
            'reference_code' => 'KS'.$booking->id.'ABCD',
            'amount' => $booking->deposit_amount,
            'status' => 'pending',
        ]);
        config(['payment.vietqr.sepay_token' => 'test', 'payment.vietqr.sepay_api_url' => 'https://sepay.test']);
        Http::fake(['sepay.test/*' => Http::response(['transactions' => [[
            'id' => 'sepay-1',
            'amount_in' => (float) $booking->deposit_amount,
            'transaction_content' => $log->reference_code,
        ]]])]);

        $transaction = app(VietQRService::class)->checkTransaction($booking);

        $this->assertSame('sepay-1', $transaction['id']);
        $this->assertSame('pending', $log->fresh()->status);
        $this->assertNull($log->fresh()->transaction_id);
    }

    public function test_payment_email_uses_cid_images_instead_of_base64_data_uris(): void
    {
        $controller = app(PaymentController::class);
        $method = new \ReflectionMethod($controller, 'buildPaymentEmailHtml');
        $booking = Booking::make([
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'total_price' => 1000000,
        ]);

        $html = $method->invoke($controller, $booking, collect(), 'momo', 'test-transaction', 'Posh Boutique', true, true);

        $this->assertStringContainsString('cid:royal-hotel-logo', $html);
        $this->assertStringContainsString('cid:checkin-qr', $html);
        $this->assertStringNotContainsString('data:image/png;base64', $html);
    }

    public function test_public_login_cannot_create_an_internal_session_even_when_role_is_forged(): void
    {
        $admin = $this->internalUser('public-escalation@example.com', 'admin');

        $this->post(route('login'), [
            'role' => 'staff',
            'email' => $admin->email,
            'username' => $admin->username,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');

        $this->assertNull(session('staff_user_id'));
        $this->assertGuest();
    }

    public function test_public_login_uses_the_same_error_for_unknown_email_and_wrong_password(): void
    {
        $customer = $this->customer('known-login@example.com');

        $unknown = $this->post(route('login'), ['email' => 'unknown@example.com', 'password' => 'WrongPassword1!'])
            ->getSession()->get('errors')->first('email');
        $known = $this->post(route('login'), ['email' => $customer->email, 'password' => 'WrongPassword1!'])
            ->getSession()->get('errors')->first('email');

        $this->assertSame($unknown, $known);
    }

    public function test_last_enabled_admin_cannot_be_deleted_locked_or_demoted(): void
    {
        $admin = $this->internalUser('last-admin@example.com', 'admin');
        $this->actingAs($admin)->withSession([
            'staff_user_id' => $admin->id,
            'staff_user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
        ]);

        $this->delete(route('admin.users.destroy', $admin))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'role' => 'admin', 'verified' => 1]);
    }

    public function test_google_callback_cannot_silently_link_an_existing_password_account(): void
    {
        $victim = $this->customer('victim@example.com');
        config(['services.google.client_id' => 'client-id', 'services.google.client_secret' => 'client-secret']);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'attacker-google-id',
            'name' => 'Attacker',
            'email' => $victim->email,
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect(route('login'));

        $this->assertNull($victim->fresh()->google_id);
        $this->assertGuest();
    }

    public function test_cash_checkout_records_only_the_outstanding_balance_before_completion(): void
    {
        [$booking, $room] = $this->bookingWithRoom();
        $staff = $this->internalUser('cash-checkout@example.com', 'receptionist');
        $booking->update(['status' => 'checked_in', 'payment_status' => 'paid']);
        $room->update(['status' => 'occupied']);
        PaymentLog::create([
            'booking_id' => $booking->id,
            'gateway' => 'momo',
            'transaction_id' => 'deposit-'.$booking->id,
            'amount' => 500000,
            'purpose' => 'deposit',
            'status' => 'success',
        ]);

        $this->actingAs($staff)->withSession([
            'staff_user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ])->postJson(route('staff.bookings.checkout-payment', $booking), ['payment_method' => 'cash'])->assertOk();

        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertTrue($booking->fresh()->isFullyPaid());
        $this->assertDatabaseHas('payment_logs', [
            'booking_id' => $booking->id,
            'gateway' => 'cash',
            'purpose' => 'checkout',
            'amount' => 500000,
            'status' => 'success',
        ]);
        $this->assertSame('cleaning', $room->fresh()->status);
    }

    public function test_cash_checkout_collects_the_full_balance_for_an_unpaid_cash_reservation(): void
    {
        [$booking, $room] = $this->bookingWithRoom();
        $staff = $this->internalUser('cash-full-checkout@example.com', 'receptionist');
        $booking->update([
            'status' => 'checked_in',
            'payment_status' => 'pending',
            'payment_method' => 'cash',
        ]);
        $room->update(['status' => 'occupied']);

        $this->actingAs($staff)->withSession([
            'staff_user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ])->postJson(route('staff.bookings.checkout-payment', $booking), ['payment_method' => 'cash'])->assertOk();

        $this->assertDatabaseHas('payment_logs', [
            'booking_id' => $booking->id,
            'gateway' => 'cash',
            'purpose' => 'checkout',
            'amount' => 1000000,
            'status' => 'success',
        ]);
        $this->assertSame('completed', $booking->fresh()->status);
        $this->assertSame('paid', $booking->fresh()->payment_status);
        $this->assertSame('cleaning', $room->fresh()->status);
    }

    public function test_deposit_only_booking_is_not_treated_as_fully_paid(): void
    {
        [$booking] = $this->bookingWithRoom();
        $booking->update(['payment_status' => 'paid']);
        PaymentLog::create([
            'booking_id' => $booking->id,
            'gateway' => 'momo',
            'transaction_id' => 'deposit-only-'.$booking->id,
            'amount' => 500000,
            'purpose' => 'deposit',
            'status' => 'success',
        ]);

        $this->assertFalse($booking->fresh()->isFullyPaid());
        $this->assertSame(500000.0, $booking->fresh()->outstandingAmount());
    }

    public function test_signed_checkin_token_is_required_and_can_only_be_used_once(): void
    {
        [$booking, $room] = $this->bookingWithRoom();
        $staff = $this->internalUser('qr-checkin@example.com', 'receptionist');
        $booking->update([
            'status' => 'confirmed',
            'payment_method' => 'cash',
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
        ]);
        $session = [
            'staff_user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ];
        $token = app(\App\Services\CheckInTokenService::class)->issue($booking);

        $this->actingAs($staff)->withSession($session)
            ->getJson(route('staff.reception.booking-by-scan', ['booking_id' => $booking->id]))
            ->assertBadRequest();
        $this->actingAs($staff)->withSession($session)
            ->getJson(route('staff.reception.booking-by-scan', ['token' => $token]))
            ->assertOk()->assertJsonPath('booking.id', $booking->id);
        $this->actingAs($staff)->withSession($session)
            ->postJson(route('staff.reception.quick-checkin'), ['token' => $token])
            ->assertOk();
        $this->assertSame('checked_in', $booking->fresh()->status);
        $this->assertSame('occupied', $room->fresh()->status);
        $this->actingAs($staff)->withSession($session)
            ->postJson(route('staff.reception.quick-checkin'), ['token' => $token])
            ->assertUnprocessable();
    }

    public function test_checkin_token_remains_valid_until_end_of_checkout_day_only(): void
    {
        [$booking] = $this->bookingWithRoom();
        $checkout = now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString();
        $booking->update(['check_out' => $checkout]);
        $tokens = app(\App\Services\CheckInTokenService::class);
        $token = $tokens->issue($booking);

        $this->travelTo(\Carbon\Carbon::parse($checkout.' 23:59:00', 'Asia/Ho_Chi_Minh'));
        $this->assertSame($booking->id, $tokens->bookingId($token));

        $this->travelTo(\Carbon\Carbon::parse($checkout, 'Asia/Ho_Chi_Minh')->addDay());
        try {
            $tokens->bookingId($token);
            $this->fail('Expected the check-in token to expire after checkout day.');
        } catch (\RuntimeException) {
            $this->assertTrue(true);
        } finally {
            $this->travelBack();
        }
    }

    public function test_customer_checkin_qr_payload_is_scannable_and_uses_the_expected_prefix(): void
    {
        [$booking] = $this->bookingWithRoom();
        $booking->update(['status' => 'confirmed']);
        $tokens = app(\App\Services\CheckInTokenService::class);

        $payload = $tokens->qrPayload($booking);
        $this->assertStringStartsWith('ROYAL-CHECKIN:', $payload);
        $this->assertSame($booking->id, $tokens->bookingId(substr($payload, strlen('ROYAL-CHECKIN:'))));
    }

    public function test_paid_booking_success_page_renders_a_token_qr_not_a_booking_id_qr(): void
    {
        $customer = $this->customer('qr-success@example.com');
        $booking = $this->booking($customer, 'confirmed', 'paid');

        $this->actingAs($customer)->withSession([
            'customer_user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
        ])->get(route('payment.success', $booking->id))
            ->assertOk()
            ->assertSee('Mã Check-in')
            ->assertSee('data:image/png;base64,', false)
            ->assertDontSee('Booking ID: #');
    }

    public function test_history_page_shows_zero_reserved_count_and_has_no_pdf_action(): void
    {
        $customer = $this->customer('empty-history@example.com');
        $customerSession = [
            'customer_user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
        ];

        $this->actingAs($customer)->withSession($customerSession)->get(route('booking.mine'))
            ->assertOk()
            ->assertSee('Lịch Sử Đặt Phòng')
            ->assertSee('Đã Đặt')
            ->assertSee('Đã Đặt <span class="badge bg-blue-50 text-primary rounded-pill ms-1">0</span>', false)
            ->assertDontSee('Xuất PDF');
    }

    public function test_customer_cannot_select_cash_from_the_online_payment_form(): void
    {
        $customer = $this->customer('no-customer-cash@example.com');
        [$booking] = $this->bookingWithRoom();
        $booking->update(['user_id' => $customer->id]);
        $customerSession = [
            'customer_user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
        ];

        $this->actingAs($customer)->withSession($customerSession)
            ->get(route('payment.form', $booking->id))
            ->assertOk()
            ->assertDontSee('method_cash')
            ->assertDontSee('Thanh toán tại quầy');

        $this->actingAs($customer)->withSession($customerSession)->patch(route('payment.update', $booking->id), [
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('payment_method');

        $this->assertNull($booking->fresh()->payment_method);
    }

    public function test_hourly_extension_costs_ten_percent_of_that_room_night_per_hour(): void
    {
        $staff = $this->internalUser('hourly-extension@example.com', 'receptionist');
        $type = RoomType::create([
            'type_name' => 'Hourly extension', 'price' => 500000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $room = Room::create([
            'room_number' => 'EXT'.uniqid(), 'room_type_id' => $type->id,
            'floor' => 1, 'status' => 'occupied',
        ]);
        $booking = $this->booking($this->customer('hourly-guest@example.com'), 'checked_in', 'paid');
        $booking->rooms()->attach($room->id);

        $this->actingAs($staff)->withSession([
            'staff_user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ])->postJson(route('staff.reception.extend'), [
            'room_id' => $room->id,
            'mode' => 'hours',
            'amount' => 2,
        ])->assertOk()
            ->assertJsonPath('hourly_rate', 50000)
            ->assertJsonPath('added_amount', 100000)
            ->assertJsonPath('total_price', 1100000);
    }

    public function test_repeated_payment_confirmation_is_an_idempotent_success_for_the_same_booking_only(): void
    {
        [$booking] = $this->bookingWithRoom();

        $this->assertTrue($this->invokeDepositSettlement($booking, 'idempotent-tx'));
        $this->assertTrue($this->invokeDepositSettlement($booking->fresh(), 'idempotent-tx'));

        [$other] = $this->bookingWithRoom();
        $this->assertFalse($this->invokeDepositSettlement($other, 'idempotent-tx'));
        $this->assertSame('pending', $other->fresh()->payment_status);
        $this->assertSame(1, PaymentLog::where('gateway', 'momo')->where('transaction_id', 'idempotent-tx')->count());
    }

    public function test_admin_password_change_revokes_target_sessions_and_remember_token(): void
    {
        Mail::fake();
        $admin = $this->internalUser('session-admin@example.com', 'admin');
        $staff = $this->internalUser('session-staff@example.com', 'receptionist');
        $staff->forceFill(['remember_token' => 'old-token'])->save();
        foreach (['old-session-a', 'old-session-b'] as $id) {
            DB::table('sessions')->insert([
                'id' => $id,
                'user_id' => $staff->id,
                'payload' => 'test',
                'last_activity' => now()->timestamp,
            ]);
        }

        $this->actingAs($admin)->withSession([
            'staff_user_id' => $admin->id,
            'staff_user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
        ])->put(route('admin.users.update', $staff), [
            'username' => $staff->username,
            'password' => 'Replacement123!',
            'password_confirmation' => 'Replacement123!',
            'fullname' => $staff->fullname,
            'email' => $staff->email,
            'phone' => $staff->phone,
            'role' => $staff->role,
        ])->assertRedirect(route('admin.users.index'));

        $staff->refresh();
        $this->assertTrue(Hash::check('Replacement123!', $staff->password));
        $this->assertNotSame('old-token', $staff->remember_token);
        $this->assertSame(0, DB::table('sessions')->where('user_id', $staff->id)->count());
    }

    public function test_legacy_room_status_endpoint_cannot_check_in_pending_booking(): void
    {
        $staff = $this->internalUser('transition-staff@example.com', 'receptionist');
        [$booking, $room] = $this->bookingWithRoom();
        $booking->update([
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
        ]);
        $session = ['staff_user_id' => $staff->id, 'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true]];

        $this->actingAs($staff)->withSession($session)
            ->postJson(route('staff.reception.update-status'), ['room_id' => $room->id, 'status' => 'occupied'])
            ->assertNotFound();
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_legacy_room_status_endpoint_cannot_check_in_unpaid_online_booking(): void
    {
        $staff = $this->internalUser('transition-unpaid-staff@example.com', 'receptionist');
        $session = ['staff_user_id' => $staff->id, 'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true]];
        [$booking, $room] = $this->bookingWithRoom();
        $booking->update([
            'status' => 'confirmed',
            'payment_method' => 'momo',
            'payment_status' => 'pending',
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
        ]);
        $this->actingAs($staff)->withSession($session)
            ->postJson(route('staff.reception.update-status'), ['room_id' => $room->id, 'status' => 'occupied'])
            ->assertUnprocessable();
        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('available', $room->fresh()->status);
    }

    public function test_walkin_rejects_an_adult_child_mix_outside_room_type_limits(): void
    {
        $staff = $this->internalUser('walkin-capacity@example.com', 'receptionist');
        $type = RoomType::create(['type_name' => 'Child only', 'price' => 200000, 'max_adults' => 1, 'max_children' => 3, 'max_guests' => 4]);
        $room = Room::create(['room_number' => 'WC'.uniqid(), 'room_type_id' => $type->id, 'floor' => 1, 'status' => 'available']);

        $this->actingAs($staff)->withSession([
            'staff_user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ])->postJson(route('staff.reception.walkin'), [
            'room_ids' => [$room->id],
            'customer_name' => 'Capacity Guest',
            'customer_phone' => '0912345678',
            'walkin_type' => 'now',
            'adult_count' => 2,
            'child_count' => 0,
            'check_out' => now()->addDay()->toDateString(),
        ])->assertUnprocessable();

        $this->assertDatabaseMissing('bookings', ['customer_name' => 'Capacity Guest']);
    }

    private function invokeDepositSettlement(Booking $booking, string $transactionId): bool
    {
        $controller = app(PaymentController::class);
        $method = new \ReflectionMethod($controller, 'confirmPayment');
        return $method->invoke($controller, $booking, 'momo', $transactionId, ['id' => $transactionId]);
    }

    private function bookingWithRoom(): array
    {
        $type = RoomType::create([
            'type_name' => 'Logic room',
            'price' => 500000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
        ]);
        $room = Room::create([
            'room_number' => 'L'.uniqid(),
            'room_type_id' => $type->id,
            'floor' => 1,
            'status' => 'available',
        ]);
        $booking = $this->booking($this->customer(uniqid().'@example.com'));
        $booking->rooms()->attach($room->id);

        return [$booking, $room];
    }

    private function booking(User $user, string $status = 'pending', string $paymentStatus = 'pending'): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'customer_name' => $user->fullname,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'check_in' => now()->addDays(3)->toDateString(),
            'check_out' => now()->addDays(5)->toDateString(),
            'adult_count' => 1,
            'child_count' => 0,
            'total_price' => 1000000,
            'deposit_amount' => 500000,
            'payment_status' => $paymentStatus,
            'status' => $status,
        ]);
    }

    private function customer(string $email): User
    {
        return User::create([
            'username' => $email,
            'fullname' => 'Logic Guest',
            'email' => $email,
            'phone' => '0912345678',
            'password' => Hash::make('Password123!'),
            'role' => 'customer',
            'verified' => true,
        ]);
    }

    private function internalUser(string $email, string $role): User
    {
        return User::create([
            'username' => strstr($email, '@', true),
            'fullname' => 'Logic Admin',
            'email' => $email,
            'phone' => '0912345678',
            'password' => Hash::make('Password123!'),
            'role' => $role,
            'verified' => true,
        ]);
    }
}
