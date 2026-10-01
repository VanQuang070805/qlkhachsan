<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\KnowledgeChunk;
use App\Models\PaymentLog;
use App\Models\RoomType;
use App\Models\User;
use App\Services\Payment\MoMoService;
use App\Services\Payment\VNPayService;
use App\Services\Payment\ZaloPayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class SecurityFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_a_payment_page(): void
    {
        $booking = $this->bookingFor($this->customer('owner@example.com'));

        $this->get(route('payment.form', $booking->id))
            ->assertRedirect(route('login'));
    }

    public function test_payment_preview_requires_the_booking_owner(): void
    {
        $owner = $this->customer('preview-owner@example.com');
        $intruder = $this->customer('preview-intruder@example.com');
        $booking = $this->bookingFor($owner);

        $this->get(route('payment.preview', $booking->id))->assertRedirect(route('login'));
        $this->asCustomer($intruder)->get(route('payment.preview', $booking->id))->assertForbidden();
        $this->asCustomer($owner)->get(route('payment.preview', $booking->id))->assertOk();
    }

    public function test_payment_webhooks_fail_closed_when_signing_secrets_are_missing(): void
    {
        config([
            'payment.momo.access_key' => '',
            'payment.momo.secret_key' => '',
            'payment.zalopay.key2' => '',
            'payment.vnpay.hash_secret' => '',
        ]);

        $this->assertFalse((new MoMoService)->verifySignature([
            'amount' => 1, 'extraData' => '', 'message' => '', 'orderId' => '1',
            'orderInfo' => '', 'orderType' => '', 'partnerCode' => '', 'payType' => '',
            'requestId' => '', 'responseTime' => '', 'resultCode' => 0, 'transId' => '',
            'signature' => hash_hmac('sha256', 'irrelevant', ''),
        ]));
        $this->assertFalse((new ZaloPayService)->verifyCallback([
            'data' => '', 'mac' => hash_hmac('sha256', '', ''),
        ]));
        $this->assertFalse((new VNPayService)->verifySecureHash([
            'vnp_SecureHash' => hash_hmac('sha512', '', ''),
        ]));
    }

    public function test_customer_cannot_open_another_customers_payment_or_cancellation(): void
    {
        $owner = $this->customer('owner@example.com');
        $intruder = $this->customer('intruder@example.com');
        $booking = $this->bookingFor($owner);

        $this->asCustomer($intruder)
            ->get(route('payment.form', $booking->id))
            ->assertForbidden();

        $this->asCustomer($intruder)
            ->get(route('booking.cancel.show', $booking->id))
            ->assertForbidden();
    }

    public function test_registration_rejects_invalid_contact_details_and_weak_password(): void
    {
        $this->from(route('register'))->post(route('register'), [
            'name' => 'A',
            'email' => 'invalid',
            'phone' => '123',
            'password' => '123',
            'password_confirmation' => '456',
        ])->assertRedirect(route('register'))
          ->assertSessionHasErrors(['name', 'email', 'phone', 'password']);
    }

    public function test_internal_routes_require_staff_context_and_role(): void
    {
        $this->get(route('receptionist.profile'))->assertRedirect(route('internalauth.login'));

        $customer = $this->customer('guest-only@example.com');
        $this->asCustomer($customer)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('internalauth.login'));
    }

    public function test_internal_login_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $this->post('/internalauth/login', ['username' => 'missing', 'password' => 'wrong']);
        }

        $this->post('/internalauth/login', ['username' => 'missing', 'password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_internal_login_errors_are_attached_to_the_relevant_field(): void
    {
        $this->from(route('internalauth.login'))
            ->post(route('internalauth.login'), ['username' => 'missing-user', 'password' => 'wrong-password'])
            ->assertRedirect(route('internalauth.login'))
            ->assertSessionHasErrors(['username'])
            ->assertSessionMissing('error');

        $staff = $this->staff('inline-error@example.com', 'receptionist');
        $this->from(route('internalauth.login'))
            ->post(route('internalauth.login'), ['username' => $staff->username, 'password' => 'wrong-password'])
            ->assertRedirect(route('internalauth.login'))
            ->assertSessionHasErrors(['username'])
            ->assertSessionMissing('error');
    }

    public function test_admin_and_receptionist_interfaces_render_in_their_own_context(): void
    {
        $admin = $this->staff('admin@example.com', 'admin');
        $this->asStaff($admin)->get(route('admin.dashboard'))->assertRedirect(route('admin.reports'));
        $this->asStaff($admin)->get(route('admin.reports'))->assertOk()->assertSee('data-chart-range', false)->assertSee('roomTypeChart')->assertSee('statusChart');
        $this->asStaff($admin)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee('data-floor-tab', false)
            ->assertSee('data-status-val="available"', false)
            ->assertSee('data-status-val="booked"', false)
            ->assertSee('window-controls--modal', false)
            ->assertSee('aria-label="Đóng trả phòng"', false)
            ->assertDontSee('data-status-val="overdue"', false);
        $this->asStaff($admin)->get(route('admin.users.index'))->assertOk()->assertDontSee('<th>Người dùng</th>', false)->assertSee('<th>Tài khoản</th>', false);
        $this->asStaff($admin)->get(route('admin.price-settings.index'))->assertOk();

        $receptionist = $this->staff('reception@example.com', 'receptionist');
        $this->asStaff($receptionist)->get(route('staff.bookings'))->assertOk();
        $this->asStaff($receptionist)->get(route('receptionist.profile'))->assertOk();
    }

    public function test_receptionist_cannot_open_admin_reports(): void
    {
        $receptionist = $this->staff('restricted@example.com', 'receptionist');

        $this->asStaff($receptionist)
            ->get(route('admin.reports'))
            ->assertRedirect(route('staff.bookings'));
    }

    public function test_secure_requests_receive_hsts_header(): void
    {
        $this->get('https://localhost/')
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_refund_workspace_and_internal_login_render(): void
    {
        $this->get(route('internalauth.login'))->assertOk()->assertSee('auth-intro', false);
        $staff = $this->staff('refund-staff@example.com', 'receptionist');
        $this->asStaff($staff)->get(route('staff.cancellations'))->assertOk()->assertSee('data-refund-search', false);
    }

    public function test_checked_in_booking_cannot_be_cancelled_by_customer(): void
    {
        $customer = $this->customer('checked-in@example.com');
        $booking = $this->bookingFor($customer);
        $booking->update(['status' => 'checked_in']);
        $this->asCustomer($customer)->post(route('booking.cancel', $booking->id), ['reason' => 'Changed plans'])
            ->assertRedirect(route('booking.mine'))->assertSessionHas('error');
        $this->assertSame('checked_in', $booking->fresh()->status);
    }

    public function test_customer_can_cancel_without_providing_a_reason(): void
    {
        $customer = $this->customer('cancel-without-reason@example.com');
        $booking = $this->bookingFor($customer);

        $this->asCustomer($customer)
            ->post(route('booking.cancel', $booking->id))
            ->assertRedirect(route('booking.mine'))
            ->assertSessionHas('success');

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertNull($booking->fresh()->cancellation_reason);
    }

    public function test_refund_can_only_be_confirmed_once_and_for_cancelled_bookings(): void
    {
        $customer = $this->customer('refund-owner@example.com');
        $booking = $this->bookingFor($customer);
        $booking->update(['refund_status' => 'eligible', 'refund_amount' => 500000, 'payment_status' => 'paid']);
        $staff = $this->staff('refund-operator@example.com', 'receptionist');
        $this->asStaff($staff)->patch(route('staff.bookings.refund', $booking->id))->assertSessionHas('error');
        $this->assertSame('eligible', $booking->fresh()->refund_status);
        $booking->update(['status' => 'cancelled']);
        $this->asStaff($staff)->patch(route('staff.bookings.refund', $booking->id))->assertSessionHas('success');
        $this->assertSame('refunded', $booking->fresh()->refund_status);
        $this->asStaff($staff)->patch(route('staff.bookings.refund', $booking->id))->assertSessionHas('error');
    }

    public function test_cash_booking_has_no_customer_payment_link_but_online_booking_keeps_it(): void
    {
        $customer = $this->customer('cash-choice@example.com');
        $cashBooking = $this->bookingFor($customer);
        $cashBooking->update(['status' => 'confirmed', 'payment_method' => 'cash']);

        $this->asCustomer($customer)->get(route('booking.mine'))
            ->assertOk()
            ->assertDontSee(route('payment.form', $cashBooking->id), false)
            ->assertSee('Thanh toán tại quầy');
        $this->assertSame('pending', $cashBooking->fresh()->payment_status);

        $onlineBooking = $this->bookingFor($customer);
        $onlineBooking->update(['payment_method' => 'vietqr']);
        $this->get(route('booking.mine'))
            ->assertSee(route('payment.form', $onlineBooking->id), false);
    }

    public function test_cash_selection_confirms_the_reservation_without_marking_the_room_or_payment_as_settled(): void
    {
        $customer = $this->customer('cash-reservation@example.com');
        $type = RoomType::create([
            'type_name' => 'Cash reservation', 'price' => 200000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $room = \App\Models\Room::create([
            'room_number' => 'CASH-1', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => 'available',
        ]);
        $booking = $this->bookingFor($customer);
        $booking->update(['payment_method' => 'cash']);
        $booking->rooms()->attach($room->id);

        $this->asCustomer($customer)->get(route('payment.show', $booking->id))
            ->assertRedirect(route('booking.success', $booking->id));

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->assertSame('pending', $booking->fresh()->payment_status);
        $this->assertSame('cash', $booking->fresh()->payment_method);
        $this->assertSame('available', $room->fresh()->status);
        $this->assertDatabaseMissing('payment_logs', ['booking_id' => $booking->id, 'status' => 'success']);
    }

    public function test_cancellation_refund_is_limited_to_successful_amount_actually_received(): void
    {
        $customer = $this->customer('refund-amount@example.com');
        $booking = $this->bookingFor($customer);
        $booking->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'vietqr',
            'check_in' => now()->addDays(12)->toDateString(),
            'deposit_amount' => 500000,
        ]);
        PaymentLog::create([
            'booking_id' => $booking->id,
            'gateway' => 'vietqr',
            'transaction_id' => 'refund-test-'.$booking->id,
            'amount' => 300000,
            'purpose' => 'deposit',
            'status' => 'success',
        ]);

        $this->asCustomer($customer)
            ->post(route('booking.cancel', $booking->id), ['reason' => 'Plans changed'])
            ->assertRedirect(route('booking.mine'));

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('eligible', $booking->fresh()->refund_status);
        $this->assertSame(300000.0, (float) $booking->fresh()->refund_amount);
    }

    public function test_customer_cannot_get_refund_for_cash_booking_that_has_not_been_collected(): void
    {
        $customer = $this->customer('cash-cancel@example.com');
        $booking = $this->bookingFor($customer);
        $booking->update([
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'payment_method' => 'cash',
            'check_in' => now()->addDays(12)->toDateString(),
        ]);

        $this->asCustomer($customer)
            ->post(route('booking.cancel', $booking->id), ['reason' => 'Plans changed'])
            ->assertRedirect(route('booking.mine'));

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('none', $booking->fresh()->refund_status);
        $this->assertSame(0.0, (float) $booking->fresh()->refund_amount);
    }

    public function test_room_booking_button_is_disabled_inline_when_guest_count_exceeds_capacity(): void
    {
        $type = RoomType::create([
            'type_name' => 'Capacity check',
            'price' => 250000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
        ]);
        \App\Models\Room::create([
            'room_number' => 'CAP-1', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => 'available',
        ]);

        $this->get(route('rooms.detail', ['id' => $type->id, 'adults' => 3, 'children' => 0]))
            ->assertOk()
            ->assertSee('id="btnBook"', false)
            ->assertSee('disabled aria-disabled=true', false)
            ->assertSee('Vượt sức chứa');
    }

    public function test_staff_room_board_marks_today_reservations_and_uses_money_actually_collected(): void
    {
        $type = RoomType::create([
            'type_name' => 'Operations check', 'price' => 250000,
            'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3,
        ]);
        $reservedRoom = \App\Models\Room::create([
            'room_number' => 'OPS-1', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => 'available',
        ]);
        $reserved = $this->bookingFor($this->customer('today-arrival@example.com'));
        $reserved->update([
            'status' => 'confirmed',
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
        ]);
        $reserved->rooms()->attach($reservedRoom->id);

        $cashRoom = \App\Models\Room::create([
            'room_number' => 'OPS-2', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => 'occupied',
        ]);
        $cashBooking = $this->bookingFor($this->customer('cash-arrival@example.com'));
        $cashBooking->update([
            'status' => 'checked_in',
            'payment_status' => 'pending',
            'payment_method' => 'cash',
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
        ]);
        $cashBooking->rooms()->attach($cashRoom->id);

        $admin = $this->staff('ops-admin@example.com', 'admin');
        $response = $this->asStaff($admin)->get(route('staff.bookings'))->assertOk();
        $html = $response->getContent();
        preg_match_all('/data-room="([^"]+)"/', $html, $matches);
        $rooms = collect($matches[1])->map(fn (string $json) => json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5), true));

        $this->assertSame('booked', $rooms->firstWhere('room_number', 'OPS-1')['ui_status']);
        $this->assertSame(0.0, (float) $rooms->firstWhere('room_number', 'OPS-2')['active_paid_amount']);
        $this->assertSame(1000000.0, (float) $rooms->firstWhere('room_number', 'OPS-2')['active_total_price']);
    }

    public function test_direct_payment_route_enforces_booking_ownership(): void
    {
        $owner = $this->customer('direct-owner@example.com');
        $intruder = $this->customer('direct-intruder@example.com');
        $booking = $this->bookingFor($owner);
        $this->asCustomer($intruder)->get(route('payment.show', $booking->id))->assertForbidden();
        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_expired_pending_hold_cannot_enter_direct_payment_route(): void
    {
        $customer = $this->customer('expired-payment@example.com');
        $booking = $this->bookingFor($customer);
        $booking->update(['payment_method' => 'cash']);

        $this->travel(11)->minutes();

        $this->asCustomer($customer)
            ->get(route('payment.show', $booking->id))
            ->assertConflict();

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('failed', $booking->fresh()->payment_status);
        $this->assertDatabaseMissing('payment_logs', [
            'booking_id' => $booking->id,
            'status' => 'success',
        ]);
        $this->travelBack();
    }

    public function test_password_reset_authorization_cannot_be_reused_for_another_email(): void
    {
        $first = $this->customer('first-reset@example.com');
        $second = $this->customer('second-reset@example.com');
        $oldHash = $second->password;
        $this->withSession(['reset_email' => $second->email, 'reset_verified' => $first->id, 'reset_verified_at' => now()->timestamp])
            ->post(route('password.reset'), ['password'=>'Replacement123!', 'password_confirmation'=>'Replacement123!'])
            ->assertRedirect(route('password.forgot'));
        $this->assertSame($oldHash, $second->fresh()->password);
    }

    public function test_booking_form_rejects_malformed_dates(): void
    {
        $this->asCustomer($this->customer('invalid-date@example.com'))
            ->getJson(route('booking.create', ['room_ids'=>[1], 'check_in'=>'invalid', 'check_out'=>'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors(['check_in', 'check_out']);
    }

    public function test_booking_information_page_renders_the_second_accessible_step(): void
    {
        $customer = $this->customer('booking-steps@example.com');
        $type = RoomType::create(['type_name'=>'Stepper room', 'price'=>320000, 'max_adults'=>2, 'max_children'=>1, 'max_guests'=>3]);
        $room = \App\Models\Room::create(['room_number'=>'STEP-1', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'available']);

        $this->asCustomer($customer)->get(route('booking.create', [
            'room_ids' => [$room->id],
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'adults' => 1,
            'children' => 0,
        ]))->assertOk()
          ->assertSee('aria-current="step"', false)
          ->assertSee('Thông tin')
          ->assertSee('civilDayNumber');
    }

    public function test_staff_walkin_checks_capacity_and_prevents_duplicate_holds(): void
    {
        $staff = $this->staff('walkin@example.com', 'receptionist');
        $type = \App\Models\RoomType::create(['type_name'=>'Test room', 'price'=>20000, 'max_adults'=>2, 'max_children'=>0, 'max_guests'=>2]);
        $room = \App\Models\Room::create(['room_number'=>'QA101', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'available']);
        $payload = ['room_ids'=>[$room->id], 'customer_name'=>'Test Guest', 'customer_phone'=>'0901234567', 'customer_email'=>'test@example.com', 'adult_count'=>3, 'child_count'=>0, 'check_out'=>now()->addDay()->toDateString(), 'walkin_type'=>'hold'];
        $this->asStaff($staff)->postJson(route('staff.reception.walkin'), $payload)->assertUnprocessable();
        $payload['adult_count'] = 1;
        $this->asStaff($staff)->postJson(route('staff.reception.walkin'), $payload)->assertOk()->assertJson(['success'=>true]);
        $this->assertSame('available', $room->fresh()->status);
        $this->asStaff($staff)->postJson(route('staff.reception.walkin'), $payload)->assertConflict();
        $this->assertDatabaseCount('bookings', 1);
    }

    public function test_checkout_fee_is_half_one_night_and_repeat_checkout_is_rejected(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->travelTo(now()->setTime(14, 1));
        $staff = $this->staff('checkout@example.com', 'receptionist');
        $type = \App\Models\RoomType::create(['type_name'=>'Test room', 'price'=>20000, 'max_adults'=>2, 'max_children'=>0, 'max_guests'=>2]);
        $room = \App\Models\Room::create(['room_number'=>'QA102', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'occupied']);
        $booking = $this->bookingFor($this->customer('checkout-guest@example.com'));
        $booking->update(['status'=>'checked_in', 'check_in'=>now()->subDays(2), 'check_out'=>now()->toDateString(), 'total_price'=>40000]);
        $booking->rooms()->attach($room->id);
        $this->asStaff($staff)->postJson(route('staff.bookings.checkout-payment', $booking->id), ['payment_method'=>'cash'])->assertOk();
        $this->assertEquals(10000, $booking->fresh()->late_checkout_fee);
        $this->assertSame('cleaning', $room->fresh()->status);
        $this->asStaff($staff)->postJson(route('staff.bookings.checkout-payment', $booking->id), ['payment_method'=>'cash'])->assertConflict();
        $this->travelBack();
    }

    public function test_room_status_cannot_bypass_checkout_settlement(): void
    {
        $staff = $this->staff('room-status@example.com', 'receptionist');
        $type = \App\Models\RoomType::create(['type_name'=>'Test room', 'price'=>20000, 'max_adults'=>2, 'max_children'=>0, 'max_guests'=>2]);
        $room = \App\Models\Room::create(['room_number'=>'QA103', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'occupied']);
        $this->asStaff($staff)->postJson(route('staff.room.status', $room->id), ['status'=>'cleaning'])->assertUnprocessable();
        $this->assertSame('occupied', $room->fresh()->status);
    }

    public function test_unpaid_room_hold_expires_and_booking_is_cancelled_after_ten_minutes(): void
    {
        $type = \App\Models\RoomType::create(['type_name'=>'Hold test', 'price'=>20000, 'max_adults'=>2, 'max_children'=>0, 'max_guests'=>2]);
        $room = \App\Models\Room::create(['room_number'=>'QA104', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'available']);
        $booking = $this->bookingFor($this->customer('hold-expiry@example.com'));
        $booking->rooms()->attach($room->id);
        $start = $booking->check_in->toDateString();
        $end = $booking->check_out->toDateString();
        $this->assertTrue(Booking::reservedRoomIds($start, $end)->contains($room->id));
        $this->travel(10)->minutes();
        $this->assertFalse(Booking::reservedRoomIds($start, $end)->contains($room->id));
        $this->artisan('bookings:expire-pending')->assertExitCode(0);
        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('failed', $booking->fresh()->payment_status);
        $booking->update(['status'=>'confirmed']);
        $this->assertTrue(Booking::reservedRoomIds($start, $end)->contains($room->id));
        $this->assertFalse(Booking::reservedRoomIds($start, $end, $booking->id)->contains($room->id));
        $this->travelBack();
    }

    public function test_customer_room_availability_disables_active_holds_and_releases_cancelled_rooms(): void
    {
        $type = RoomType::create(['type_name'=>'Availability test', 'price'=>20000, 'max_adults'=>2, 'max_children'=>0, 'max_guests'=>2]);
        $heldRoom = \App\Models\Room::create(['room_number'=>'AVL-1', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'available']);
        $freeRoom = \App\Models\Room::create(['room_number'=>'AVL-2', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'available']);
        $occupiedRoom = \App\Models\Room::create(['room_number'=>'AVL-3', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'occupied']);
        $checkIn = now()->addDays(5)->toDateString();
        $checkOut = now()->addDays(7)->toDateString();
        $booking = $this->bookingFor($this->customer('availability-hold@example.com'));
        $booking->update(['check_in'=>$checkIn, 'check_out'=>$checkOut]);
        $booking->rooms()->attach($heldRoom->id);

        $detailUrl = route('rooms.detail', $type->id);
        $this->getJson($detailUrl.'?check_in='.$checkIn.'&check_out='.$checkOut)
            ->assertOk()
            ->assertJsonPath('rooms.0.id', $heldRoom->id)
            ->assertJsonPath('rooms.0.available', false)
            ->assertJsonPath('rooms.0.status', 'reserved');

        $search = $this->get(route('rooms.search', [
            'check_in'=>$checkIn, 'check_out'=>$checkOut, 'adults'=>1, 'children'=>0,
        ]))->assertOk();
        $search->assertSee('data-room-id="'.$heldRoom->id.'"', false)
            ->assertSee('disabled aria-disabled="true"', false)
            ->assertSee('data-room-id="'.$freeRoom->id.'"', false)
            ->assertSee('data-room-id="'.$occupiedRoom->id.'"', false);
        $this->get($detailUrl.'?check_in='.$checkIn.'&check_out='.$checkOut)
            ->assertOk()
            ->assertSee('data-room-id="'.$heldRoom->id.'"', false)
            ->assertSee('disabled aria-disabled="true"', false)
            ->assertDontSee('Đã có khách')
            ->assertDontSee('Còn trống');

        $booking->update(['status'=>'cancelled']);
        $this->getJson($detailUrl.'?check_in='.$checkIn.'&check_out='.$checkOut)
            ->assertOk()
            ->assertJsonPath('rooms.0.available', true)
            ->assertJsonPath('available_ids.0', $heldRoom->id);
    }

    public function test_room_type_base_price_is_shared_by_customer_booking_and_internal_room_board(): void
    {
        $type = RoomType::create(['type_name'=>'Price sync single', 'price'=>200000, 'max_adults'=>1, 'max_children'=>0, 'max_guests'=>1]);
        $room = \App\Models\Room::create(['room_number'=>'RATE-1', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'available']);
        $checkIn = now()->addDays(5)->toDateString();
        $checkOut = now()->addDays(6)->toDateString();
        $customer = $this->customer('price-sync@example.com');

        $this->get(route('rooms.detail', $type->id))
            ->assertOk()
            ->assertSee('200.000');
        $this->asCustomer($customer)->get(route('booking.create', [
            'room_ids'=>[$room->id], 'check_in'=>$checkIn, 'check_out'=>$checkOut, 'adults'=>1, 'children'=>0,
        ]))->assertOk()->assertSee('200.000');

        $admin = $this->staff('price-sync-admin@example.com', 'admin');
        $response = $this->asStaff($admin)->get(route('staff.bookings'))->assertOk();
        preg_match_all('/data-room="([^"]+)"/', $response->getContent(), $matches);
        $roomRows = collect($matches[1])->map(fn (string $json) => json_decode(html_entity_decode($json, ENT_QUOTES | ENT_HTML5), true));
        $this->assertSame(200000.0, (float) $roomRows->firstWhere('room_number', 'RATE-1')['price']);
    }

    public function test_report_filters_use_database_booking_statuses(): void
    {
        $admin = $this->staff('report-filter@example.com', 'admin');
        $this->asStaff($admin)
            ->get(route('admin.reports', ['status' => 'checked_in']))
            ->assertOk()
            ->assertSee('Đang lưu trú');

        $this->asStaff($admin)
            ->get(route('admin.reports', ['status' => 'invented-state']))
            ->assertSessionHasErrors(['status']);
    }

    public function test_checkout_does_not_charge_late_fee_during_the_one_hour_grace_period(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $this->travelTo(now()->setTime(12, 59));
        $staff = $this->staff('checkout-grace@example.com', 'receptionist');
        $type = \App\Models\RoomType::create(['type_name'=>'Grace room', 'price'=>20000, 'max_adults'=>2, 'max_children'=>0, 'max_guests'=>2]);
        $room = \App\Models\Room::create(['room_number'=>'QA105', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'occupied']);
        $booking = $this->bookingFor($this->customer('checkout-grace-guest@example.com'));
        $booking->update(['status'=>'checked_in', 'check_in'=>now()->subDays(2), 'check_out'=>now()->toDateString(), 'total_price'=>40000]);
        $booking->rooms()->attach($room->id);

        $this->asStaff($staff)
            ->postJson(route('staff.bookings.checkout-payment', $booking->id), ['payment_method'=>'cash'])
            ->assertOk();

        $this->assertEquals(0, $booking->fresh()->late_checkout_fee);
        $this->travelBack();
    }

    public function test_registration_sends_an_otp_email_and_stores_verification_context(): void
    {
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->once()->andReturnNull();

        $this->post(route('register'), [
            'name' => 'New Guest',
            'email' => 'new-guest@example.com',
            'phone' => '0901234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertRedirect(route('verify'));

        $storedOtp = User::where('email', 'new-guest@example.com')->value('otp_code');
        $this->assertNotNull($storedOtp);
        $this->assertGreaterThan(10, strlen($storedOtp));
        $this->assertSame('new-guest@example.com', session('pending_verify_email'));
    }

    public function test_registration_mail_failure_keeps_the_created_account_in_the_otp_flow(): void
    {
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $payload = [
            'name' => 'Pending Guest',
            'email' => 'pending-guest@example.com',
            'phone' => '0901234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ];

        $this->postJson(route('register'), $payload)
            ->assertOk()
            ->assertJson(['success' => true, 'redirect' => route('verify')])
            ->assertSessionHas('success', 'Đăng ký thành công! Mã OTP sẽ được gửi đến ' . $payload['email'] . '.');

        $user = User::where('email', $payload['email'])->firstOrFail();
        $originalPassword = $user->password;
        $this->assertFalse($user->verified);
        $this->assertSame($payload['email'], session('pending_verify_email'));

        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->never();
        $payload['password'] = $payload['password_confirmation'] = 'AnotherPass456!';
        $this->postJson(route('register'), $payload)
            ->assertOk()
            ->assertJson(['success' => true, 'redirect' => route('verify')])
            ->assertSessionHas('success', 'Tài khoản đang chờ xác thực. Vui lòng nhập OTP hoặc yêu cầu gửi lại mã.');

        $this->assertSame($originalPassword, $user->fresh()->password);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_form_and_backend_share_the_same_password_policy(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('minlength="10"', false)
            ->assertSee('data-pattern-message="Mật khẩu phải có chữ hoa, chữ thường và số."', false);

        $this->postJson(route('register'), [
            'name' => 'Policy Guest',
            'email' => 'policy@example.com',
            'phone' => '0901234567',
            'password' => 'lowercaseonly1',
            'password_confirmation' => 'lowercaseonly1',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password']);

        $this->assertDatabaseMissing('users', ['email' => 'policy@example.com']);
    }

    public function test_customer_can_complete_the_password_reset_flow(): void
    {
        $otp = null;
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->once()
            ->withArgs(function (string $body) use (&$otp) {
                preg_match('/\b(\d{6})\b/', $body, $matches);
                $otp = $matches[1] ?? null;
                return true;
            })->andReturnNull();
        $customer = $this->customer('reset-flow@example.com');

        $this->post(route('password.forgot'), ['email' => $customer->email])
            ->assertRedirect(route('password.verify-otp'));

        $this->assertNotNull($otp);
        $this->post(route('password.verify-otp'), ['otp' => $otp])
            ->assertRedirect(route('password.reset'));
        $this->post(route('password.reset'), [
            'password' => 'Replacement123!',
            'password_confirmation' => 'Replacement123!',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('Replacement123!', $customer->fresh()->password));
        $this->assertNull(session('reset_email'));
    }

    public function test_ajax_password_reset_returns_json_success_instead_of_login_html(): void
    {
        $customer = $this->customer('reset-ajax@example.com');

        $this->withSession([
            'reset_email' => $customer->email,
            'reset_verified' => $customer->id,
            'reset_verified_at' => now()->timestamp,
        ])->postJson(route('password.reset'), [
            'password' => 'Replacement123!',
            'password_confirmation' => 'Replacement123!',
        ])->assertOk()->assertJson([
            'success' => true,
            'redirect' => route('login'),
        ]);

        $this->assertTrue(Hash::check('Replacement123!', $customer->fresh()->password));
    }

    public function test_password_reset_mismatch_is_reported_on_confirmation_field(): void
    {
        $customer = $this->customer('reset-mismatch@example.com');
        $previousHash = $customer->password;

        $this->withSession([
            'reset_email' => $customer->email,
            'reset_verified' => $customer->id,
            'reset_verified_at' => now()->timestamp,
        ])->postJson(route('password.reset'), [
            'password' => 'Replacement123!',
            'password_confirmation' => 'Different123!',
        ])->assertUnprocessable()->assertJsonValidationErrors(['password_confirmation']);

        $this->assertSame($previousHash, $customer->fresh()->password);
    }

    public function test_password_reset_response_does_not_disclose_account_or_mail_delivery_state(): void
    {
        $customer = $this->customer('mail-failure@example.com');
        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP unavailable'));

        $existing = $this->post(route('password.forgot'), ['email' => $customer->email]);
        $existing->assertRedirect(route('password.verify-otp'))
            ->assertSessionHas('success', 'Nếu email tồn tại, mã OTP đã được gửi đến hộp thư của bạn.')
            ->assertSessionMissing('error');

        \Illuminate\Support\Facades\Mail::shouldReceive('raw')->never();
        $missing = $this->post(route('password.forgot'), ['email' => 'missing@example.com']);
        $missing->assertRedirect(route('password.verify-otp'))
            ->assertSessionHas('success', 'Nếu email tồn tại, mã OTP đã được gửi đến hộp thư của bạn.')
            ->assertSessionMissing('error');
    }

    public function test_reset_otp_page_receives_the_session_email_and_requires_reset_context(): void
    {
        $this->get(route('password.verify-otp'))->assertRedirect(route('password.forgot'));

        $this->withSession(['reset_email' => 'otp-view@example.com'])
            ->get(route('password.verify-otp'))
            ->assertOk()
            ->assertViewHas('email', 'otp-view@example.com')
            ->assertSee('otp-view@example.com');
    }

    public function test_customer_login_sets_a_remember_cookie_when_requested(): void
    {
        $customer = $this->customer('remembered@example.com');

        $response = $this->post(route('login'), [
            'role' => 'customer',
            'email' => $customer->email,
            'password' => 'password123',
            'remember' => '1',
        ]);

        $response->assertRedirect(route('home'));
        $response->assertCookie(auth()->guard()->getRecallerName());
        $response->assertCookie('royal_remembered_email', $customer->email);
    }

    public function test_chatbot_validates_input_and_uses_current_room_data_without_an_ai_key(): void
    {
        config(['services.royal_ai.key' => null]);
        RoomType::create([
            'type_name' => 'Phòng Kiểm Thử',
            'price' => 765000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
            'description' => 'Không gian thử nghiệm',
        ]);

        $this->postJson(route('chatbot.api'), ['message' => ''])->assertUnprocessable();
        $this->postJson(route('chatbot.api'), ['message' => 'Giá phòng hiện tại?'])
            ->assertOk()
            ->assertJsonPath('source', 'hotel')
            ->assertJsonFragment(['reply' => "Bảng giá phòng hiện tại của Posh Boutique:\n- Phòng Kiểm Thử: 765.000 VNĐ/đêm (Tối đa 3 người)\n\nBạn có thể nhấn vào mục 'Tìm phòng trống' trên thanh menu để chọn ngày và đặt phòng nhé."]);
    }

    public function test_chatbot_has_a_dedicated_per_ip_rate_limit(): void
    {
        config(['services.royal_ai.key' => null]);

        for ($request = 0; $request < 12; $request++) {
            $this->postJson(route('chatbot.api'), ['message' => 'Xin chào'])->assertOk();
        }

        $this->postJson(route('chatbot.api'), ['message' => 'Xin chào'])->assertTooManyRequests();
    }

    public function test_chatbot_can_use_the_configured_ai_without_exposing_its_key(): void
    {
        config([
            'services.royal_ai.key' => 'server-only-test-key',
            'services.royal_ai.endpoint' => 'https://ai.example.test/chat',
            'services.royal_ai.model' => 'royal-test-model',
        ]);
        Http::fake(['ai.example.test/*' => Http::response([
            'choices' => [['message' => ['content' => 'Bầu trời thường có màu xanh vì ánh sáng xanh bị tán xạ mạnh hơn trong khí quyển.']]],
        ])]);

        $this->postJson(route('chatbot.api'), [
            'message' => 'Vì sao bầu trời có màu xanh?',
            'history' => [['role' => 'assistant', 'content' => 'Bạn muốn trò chuyện về điều gì?']],
        ])->assertOk()
          ->assertJson(['source' => 'ai', 'reply' => 'Bầu trời thường có màu xanh vì ánh sáng xanh bị tán xạ mạnh hơn trong khí quyển.'])
          ->assertDontSee('server-only-test-key');

        Http::assertSent(fn ($request) => $request->url() === 'https://ai.example.test/chat'
            && $request->hasHeader('Authorization', 'Bearer server-only-test-key')
            && $request['model'] === 'royal-test-model');
    }

    public function test_chatbot_tool_only_returns_bookings_for_the_signed_in_customer(): void
    {
        $owner = $this->customer('chat-owner@example.com');
        $other = $this->customer('chat-other@example.com');
        $ownedBooking = $this->bookingFor($owner);
        $otherBooking = $this->bookingFor($other);
        config([
            'services.royal_ai.key' => 'server-only-test-key',
            'services.royal_ai.endpoint' => 'https://ai.example.test/chat',
            'services.royal_ai.model' => 'royal-test-model',
        ]);
        Http::fake(['ai.example.test/*' => Http::sequence()
            ->push(['choices' => [['message' => [
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => [[
                    'id' => 'call_booking',
                    'type' => 'function',
                    'function' => ['name' => 'get_booking_for_user', 'arguments' => '{}'],
                ]],
            ]]]])
            ->push(['choices' => [['message' => ['role' => 'assistant', 'content' => "Kỳ nghỉ #{$ownedBooking->id}"]]]]),
        ]);

        $this->asCustomer($owner)->postJson(route('chatbot.api'), ['message' => 'Cho tôi xem kỳ nghỉ của tôi'])
            ->assertOk()->assertJsonPath('reply', "Kỳ nghỉ #{$ownedBooking->id}");

        Http::assertSent(function ($request) use ($ownedBooking, $otherBooking) {
            $toolMessage = collect($request['messages'] ?? [])->firstWhere('role', 'tool');
            if (! $toolMessage) return false;
            return str_contains($toolMessage['content'], '"booking_id":'.$ownedBooking->id)
                && ! str_contains($toolMessage['content'], '"booking_id":'.$otherBooking->id);
        });
    }

    public function test_local_knowledge_retrieval_and_daily_report_snapshot_work(): void
    {
        $this->artisan('knowledge:index', ['--no-embeddings' => true])->assertSuccessful();
        $this->assertGreaterThan(0, KnowledgeChunk::query()->count());

        $results = app(\App\Services\RoyalKnowledgeService::class)->search('Giờ nhận phòng và trả phòng');
        $this->assertNotEmpty($results);
        $this->assertStringContainsString('Nhận và trả phòng', $results[0]['source']);

        $this->artisan('reports:snapshot', ['--date' => now()->toDateString()])->assertSuccessful();
        $this->assertSame(now()->toDateString(), \App\Models\ReportSnapshot::firstOrFail()->snapshot_date->toDateString());
    }

    public function test_knowledge_search_uses_semantic_embeddings_when_available(): void
    {
        config([
            'services.royal_ai.key' => 'server-only-test-key',
            'services.royal_ai.vector_store_id' => null,
            'services.royal_ai.embedding_model' => 'text-embedding-test',
        ]);
        KnowledgeChunk::create([
            'source' => 'Posh Boutique · Arrival',
            'title' => 'Arrival',
            'content' => 'Nội dung không trùng từ khóa truy vấn.',
            'content_hash' => hash('sha256', 'semantic-arrival'),
            'embedding' => [1.0, 0.0],
            'embedding_model' => 'text-embedding-test',
        ]);
        KnowledgeChunk::create([
            'source' => 'Posh Boutique · Other',
            'title' => 'Other',
            'content' => 'Một đoạn nội dung khác.',
            'content_hash' => hash('sha256', 'semantic-other'),
            'embedding' => [0.0, 1.0],
            'embedding_model' => 'text-embedding-test',
        ]);
        Cache::forget('royal-knowledge-v2');
        Http::fake(['api.openai.com/v1/embeddings' => Http::response([
            'data' => [['index' => 0, 'embedding' => [1.0, 0.0]]],
        ])]);

        $results = app(\App\Services\RoyalKnowledgeService::class)->search('semantic only request');

        $this->assertSame('Posh Boutique · Arrival', $results[0]['source']);
        $this->assertSame(1.0, $results[0]['semantic_score']);
    }

    public function test_opening_room_matrix_does_not_mutate_maintenance_rooms(): void
    {
        $staff = $this->staff('matrix-readonly@example.com', 'receptionist');
        $type = \App\Models\RoomType::create(['type_name' => 'Maintenance test', 'price' => 20000, 'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2]);
        $room = \App\Models\Room::create(['room_number' => 'QA-M1', 'room_type_id' => $type->id, 'floor' => 1, 'status' => 'maintenance']);

        $this->asStaff($staff)->get(route('staff.bookings'))->assertOk();

        $this->assertSame('maintenance', $room->fresh()->status);
    }

    public function test_chatbot_stream_emits_incremental_events_and_completion_metadata(): void
    {
        config(['services.royal_ai.key' => null]);

        $response = $this->post(route('chatbot.stream'), ['message' => 'Giờ nhận phòng là khi nào?']);

        $response->assertOk()->assertHeader('Content-Type', 'text/event-stream; charset=UTF-8');
        $content = $response->streamedContent();
        $this->assertStringContainsString('"delta"', $content);
        $this->assertStringContainsString('"done":true', $content);
    }

    public function test_booking_steps_expose_one_current_stage_and_completed_links(): void
    {
        $html = view('client.partials.booking-steps', [
            'currentStep' => 2,
            'stepLinks' => [1 => '/rooms/1'],
        ])->render();

        $this->assertSame(1, substr_count($html, 'aria-current="step"'));
        $this->assertStringContainsString('href="/rooms/1"', $html);
        $this->assertStringContainsString('is-complete', $html);
        $this->assertStringContainsString('is-upcoming', $html);
    }

    public function test_google_sign_in_is_customer_only_and_fails_gracefully_without_server_credentials(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get(route('login'))->assertOk()->assertSee(route('auth.google'), false)->assertSee('autocomplete="current-password"', false);
        $this->get(route('auth.google'))->assertRedirect(route('login'))->assertSessionHas('error');
    }

    public function test_google_callback_creates_and_persists_a_verified_customer_identity(): void
    {
        config(['services.google.client_id' => 'client-id', 'services.google.client_secret' => 'client-secret']);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-user-123',
            'name' => 'Google Guest',
            'email' => 'Google.Guest@example.com',
            'avatar' => 'https://example.com/google-avatar.jpg',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home'))
            ->assertSessionHas('customer_user_id');

        $this->assertDatabaseHas('users', [
            'email' => 'google.guest@example.com',
            'google_id' => 'google-user-123',
            'avatar_url' => 'https://example.com/google-avatar.jpg',
            'role' => 'customer',
            'verified' => 1,
        ]);
    }

    public function test_completed_booking_owner_can_submit_one_review(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        $customer = $this->customer('review-owner@example.com');
        $type = RoomType::create(['type_name'=>'Review room', 'price'=>320000, 'max_adults'=>2, 'max_children'=>1, 'max_guests'=>3]);
        $room = \App\Models\Room::create(['room_number'=>'REVIEW-1', 'room_type_id'=>$type->id, 'floor'=>1, 'status'=>'available']);
        $booking = $this->bookingFor($customer);
        $booking->rooms()->attach($room->id);
        $booking->update(['status'=>'completed']);

        $this->asCustomer($customer)->get(route('reviews.create', ['booking_id'=>$booking->id, 'room_type_id'=>$type->id]))
            ->assertOk()->assertSee('role="radiogroup"', false)->assertSee('resize:vertical', false);
        $this->asCustomer($customer)->post(route('reviews.store'), ['booking_id'=>$booking->id, 'room_type_id'=>$type->id, 'rating'=>5, 'comment'=>'Một kỳ nghỉ rất dễ chịu.'])
            ->assertRedirect(route('rooms.detail', $type->id));
        $this->assertDatabaseHas('reviews', ['user_id'=>$customer->id, 'room_type_id'=>$type->id, 'rating'=>5]);

        $this->asCustomer($customer)->post(route('reviews.store'), ['booking_id'=>$booking->id, 'room_type_id'=>$type->id, 'rating'=>4])
            ->assertRedirect(route('rooms.detail', $type->id));
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_royal_pagination_marks_one_current_page_and_disables_the_range_end(): void
    {
        $paginator = new LengthAwarePaginator(range(1, 10), 25, 10, 1, ['path' => '/admin/users']);
        $html = view('pagination.royal', [
            'paginator' => $paginator,
            'elements' => [[1 => '/admin/users?page=1', 2 => '/admin/users?page=2', 3 => '/admin/users?page=3']],
        ])->render();

        $this->assertSame(1, substr_count($html, 'aria-current="page"'));
        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringContainsString('rel="next"', $html);
    }

    private function customer(string $email): User
    {
        return User::create([
            'username' => $email,
            'fullname' => 'Royal Guest',
            'email' => $email,
            'phone' => '0912345678',
            'password' => Hash::make('password123'),
            'role' => 'customer',
            'verified' => true,
        ]);
    }

    private function bookingFor(User $user): Booking
    {
        return Booking::create([
            'user_id' => $user->id,
            'customer_name' => $user->fullname,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'check_in' => now()->addDays(5)->toDateString(),
            'check_out' => now()->addDays(7)->toDateString(),
            'adult_count' => 1,
            'child_count' => 0,
            'total_price' => 1000000,
            'deposit_amount' => 500000,
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
    }

    private function staff(string $email, string $role): User
    {
        return User::create([
            'username' => $email,
            'fullname' => 'Royal Staff',
            'email' => $email,
            'phone' => '0912345678',
            'password' => Hash::make('password123'),
            'role' => $role,
            'verified' => true,
        ]);
    }

    private function asCustomer(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'customer_user_id' => $user->id,
            'user_id' => $user->id,
            'customer_user' => ['id' => $user->id, 'role' => 'customer', 'verified' => true],
            'user' => ['id' => $user->id, 'role' => 'customer', 'verified' => true],
        ]);
    }

    private function asStaff(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'staff_user_id' => $user->id,
            'user_id' => $user->id,
            'staff_user' => ['id' => $user->id, 'role' => $user->role, 'verified' => true],
            'user' => ['id' => $user->id, 'fullname' => $user->fullname, 'role' => $user->role, 'verified' => true],
        ]);
    }
}
