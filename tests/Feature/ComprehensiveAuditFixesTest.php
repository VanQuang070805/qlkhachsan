<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Role;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use App\Services\StayExtensionService;
use App\Services\SyncOverdueRooms;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ComprehensiveAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_auto_overdue_marks_room_after_30_minutes_or_immediately_when_next_booking_exists_and_resets_on_extension(): void
    {
        $type = RoomType::create([
            'type_name' => 'Suite Overdue Test',
            'price' => 1000000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
        ]);

        $roomNoNext = Room::create([
            'room_number' => '301',
            'room_type_id' => $type->id,
            'floor' => 3,
            'status' => Room::STATUS_OCCUPIED,
        ]);

        $roomWithNext = Room::create([
            'room_number' => '302',
            'room_type_id' => $type->id,
            'floor' => 3,
            'status' => Room::STATUS_OCCUPIED,
        ]);

        $checkoutDay = '2026-12-10';

        $bookingNoNext = Booking::create([
            'customer_name' => 'Khach Khong Co Don Ke Tiep',
            'customer_email' => 'nonext@example.test',
            'customer_phone' => '0911222333',
            'check_in' => '2026-12-09',
            'check_out' => $checkoutDay,
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 1000000,
            'deposit_amount' => 500000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'checked_in',
        ]);
        $bookingNoNext->rooms()->attach($roomNoNext->id);

        $bookingWithNext = Booking::create([
            'customer_name' => 'Khach Co Don Ke Tiep',
            'customer_email' => 'withnext@example.test',
            'customer_phone' => '0911222444',
            'check_in' => '2026-12-09',
            'check_out' => $checkoutDay,
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 1000000,
            'deposit_amount' => 500000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'checked_in',
        ]);
        $bookingWithNext->rooms()->attach($roomWithNext->id);

        $upcomingBooking = Booking::create([
            'customer_name' => 'Khach Sap Den',
            'customer_email' => 'upcoming@example.test',
            'customer_phone' => '0911222555',
            'check_in' => $checkoutDay,
            'check_out' => '2026-12-12',
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 2000000,
            'deposit_amount' => 1000000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
        $upcomingBooking->rooms()->attach($roomWithNext->id);

        $sync = app(SyncOverdueRooms::class);

        // 12:05 (5 minutes past 12:00 checkout): room WITH upcoming reservation turns overdue immediately; room WITHOUT stays occupied.
        Carbon::setTestNow(Carbon::parse('2026-12-10 12:05:00', 'Asia/Ho_Chi_Minh'));
        $sync->sync();
        $this->assertSame(Room::STATUS_OCCUPIED, $roomNoNext->fresh()->status);
        $this->assertSame(Room::STATUS_OVERDUE, $roomWithNext->fresh()->status);

        // 12:30 (30 minutes past 12:00 checkout): room WITHOUT upcoming reservation also turns overdue.
        Carbon::setTestNow(Carbon::parse('2026-12-10 12:30:00', 'Asia/Ho_Chi_Minh'));
        $sync->sync();
        $this->assertSame(Room::STATUS_OVERDUE, $roomNoNext->fresh()->status);

        // When staff extends bookingNoNext by 2 hours (new due_at = 14:00 > 12:30), room resets to occupied.
        app(StayExtensionService::class)->extend($bookingNoNext->id, 'hours', 2, true);
        $this->assertSame(Room::STATUS_OCCUPIED, $roomNoNext->fresh()->status);
    }

    public function test_customer_can_return_from_payment_page_to_edit_pending_booking_without_self_blocking(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-12-01 10:00:00', 'Asia/Ho_Chi_Minh'));

        $customer = User::create([
            'username' => 'cust_edit_hold',
            'fullname' => 'Khach Ban Dau',
            'email' => 'cust-hold@example.test',
            'phone' => '0912345678',
            'password' => Hash::make('secret123'),
            'role' => 'customer',
            'verified' => true,
        ]);

        $type = RoomType::create([
            'type_name' => 'Deluxe Hold Edit',
            'price' => 800000,
            'max_adults' => 2,
            'max_children' => 0,
            'max_guests' => 2,
        ]);
        $room = Room::create([
            'room_number' => '205',
            'room_type_id' => $type->id,
            'floor' => 2,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        $session = [
            'customer_user_id' => $customer->id,
            'user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'fullname' => $customer->fullname, 'email' => $customer->email, 'phone' => $customer->phone, 'role' => 'customer', 'verified' => true],
            'user' => ['id' => $customer->id, 'fullname' => $customer->fullname, 'email' => $customer->email, 'phone' => $customer->phone, 'role' => 'customer', 'verified' => true],
        ];

        $storeResp = $this->withSession($session)->actingAs($customer)->post(route('booking.store'), [
            'room_ids' => [$room->id],
            'check_in' => '2026-12-05',
            'check_out' => '2026-12-07',
            'adult_count' => 2,
            'child_count' => 0,
            'customer_name' => 'Khach Ban Dau',
            'customer_email' => 'cust-hold@example.test',
            'customer_phone' => '0912345678',
        ]);

        $booking = Booking::firstOrFail();
        $storeResp->assertRedirect(route('payment.form', $booking->id));

        // Payment page includes booking_id in the edit link.
        $this->withSession($session)->actingAs($customer)->get(route('payment.form', $booking->id))
            ->assertOk()
            ->assertSee('booking_id=' . $booking->id, false);

        // Revisiting booking.create with booking_id succeeds instead of self-blocking.
        $this->withSession($session)->actingAs($customer)->get(route('booking.create', [
            'room_ids' => [$room->id],
            'check_in' => '2026-12-05',
            'check_out' => '2026-12-07',
            'adults' => 2,
            'children' => 0,
            'booking_id' => $booking->id,
        ]))->assertOk()->assertSee('name="booking_id" value="' . $booking->id . '"', false);

        // Submitting updated guest info updates the same pending booking in place.
        $updateResp = $this->withSession($session)->actingAs($customer)->post(route('booking.store'), [
            'booking_id' => $booking->id,
            'room_ids' => [$room->id],
            'check_in' => '2026-12-05',
            'check_out' => '2026-12-07',
            'adult_count' => 2,
            'child_count' => 0,
            'customer_name' => 'Khach Da Cap Nhat',
            'customer_email' => 'updated-hold@example.test',
            'customer_phone' => '0987654321',
        ]);

        $updateResp->assertRedirect(route('payment.form', $booking->id));
        $this->assertSame(1, Booking::count());
        $this->assertSame('Khach Da Cap Nhat', $booking->fresh()->customer_name);
        $this->assertSame('0987654321', $booking->fresh()->customer_phone);
    }

    public function test_vnpay_invalid_signature_parses_integer_booking_id_and_otp_five_wrong_attempts_allows_resend(): void
    {
        $customer = User::create([
            'username' => 'otp_user_test',
            'fullname' => 'OTP User',
            'email' => 'otp-user@example.test',
            'phone' => '0911000111',
            'password' => Hash::make('secret123'),
            'role' => 'customer',
            'verified' => false,
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $booking = Booking::create([
            'user_id' => $customer->id,
            'customer_name' => 'VNPay Test',
            'customer_email' => 'otp-user@example.test',
            'customer_phone' => '0911000111',
            'check_in' => '2026-12-15',
            'check_out' => '2026-12-16',
            'adult_count' => 1,
            'child_count' => 0,
            'total_price' => 500000,
            'deposit_amount' => 250000,
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // vnp_TxnRef contains string composite reference "ID_deposit_TIMESTAMP"
        $this->get(route('webhook.vnpay.return', [
            'vnp_TxnRef' => $booking->id . '_deposit_1700000000',
            'vnp_SecureHash' => 'invalid_signature',
        ]))->assertRedirect(route('payment.error', $booking->id));

        // 5 wrong OTP attempts expire the OTP without falsely triggering permanent admin-lockout.
        for ($i = 1; $i <= 5; $i++) {
            $resp = $this->withSession(['pending_verify_email' => $customer->email])
                ->postJson('/verify', ['otp' => '000000']);
            $resp->assertStatus(422);
        }

        $this->assertNotNull($customer->fresh()->otp_code);
        $this->assertTrue($customer->fresh()->otp_expires_at->isPast());

        // Attempting a 6th submit tells the user OTP is expired (not "locked by administrator").
        $sixth = $this->withSession(['pending_verify_email' => $customer->email])
            ->postJson('/verify', ['otp' => '123456']);
        $sixth->assertStatus(422)->assertJsonFragment([
            'message' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại.',
        ]);
    }

    public function test_reports_csv_export_includes_details_for_admin_and_omits_customer_pii_for_staff(): void
    {
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Quản trị viên']);
        $staffRole = Role::firstOrCreate(['slug' => 'receptionist'], ['name' => 'Lễ tân']);

        $admin = User::create([
            'username' => 'csv_admin',
            'fullname' => 'CSV Admin',
            'email' => 'csv-admin@example.test',
            'password' => Hash::make('123456'),
            'role' => 'admin',
            'role_id' => $adminRole->id,
            'verified' => true,
        ]);

        $staff = User::create([
            'username' => 'csv_staff',
            'fullname' => 'CSV Staff',
            'email' => 'csv-staff@example.test',
            'password' => Hash::make('123456'),
            'role' => 'receptionist',
            'role_id' => $staffRole->id,
            'verified' => true,
        ]);
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'rp_view_reports'],
        ]);

        $type = RoomType::create([
            'type_name' => 'Export Test Type',
            'price' => 900000,
            'max_adults' => 2,
            'max_children' => 0,
            'max_guests' => 2,
        ]);
        $room = Room::create([
            'room_number' => '401',
            'room_type_id' => $type->id,
            'floor' => 4,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        $booking = Booking::create([
            'customer_name' => 'Khach Bi Mat PII',
            'customer_email' => 'secret-pii@example.test',
            'customer_phone' => '0933444555',
            'check_in' => '2026-12-02',
            'check_out' => '2026-12-04',
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 1800000,
            'deposit_amount' => 900000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
        $booking->rooms()->attach($room->id);

        $adminCsv = $this->withSession([
            'admin_user_id' => $admin->id,
            'user_id' => $admin->id,
            'admin_user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
            'user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
        ])->actingAs($admin)->get(route('admin.reports.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('BÁO CÁO THỐNG KÊ VẬN HÀNH ROSALIZA HOTEL', $adminCsv);
        $this->assertStringContainsString('Khach Bi Mat PII', $adminCsv);
        $this->assertStringContainsString('0933444555', $adminCsv);

        $this->flushSession();

        $staffCsv = $this->withSession([
            'staff_user_id' => $staff->id,
            'user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
            'user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ])->actingAs($staff)->get(route('staff.reports.export'))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('BÁO CÁO THỐNG KÊ VẬN HÀNH ROSALIZA HOTEL', $staffCsv);
        $this->assertStringNotContainsString('Khach Bi Mat PII', $staffCsv);
        $this->assertStringNotContainsString('0933444555', $staffCsv);
    }

    public function test_customer_can_submit_multiple_reviews_and_eligible_cancellation_requires_and_displays_bank_refund_info_to_staff(): void
    {
        $customer = User::create([
            'username' => 'multi_review_cust',
            'fullname' => 'Nguyen Thi Lan',
            'email' => 'lan-review@example.test',
            'phone' => '0912000999',
            'password' => Hash::make('secret123'),
            'role' => 'customer',
            'verified' => true,
        ]);

        $staffRole = Role::firstOrCreate(['slug' => 'receptionist'], ['name' => 'Lễ tân']);
        $staff = User::create([
            'username' => 'refund_staff_check',
            'fullname' => 'Staff Refund',
            'email' => 'refund-staff-check@example.test',
            'password' => Hash::make('123456'),
            'role' => 'receptionist',
            'role_id' => $staffRole->id,
            'verified' => true,
        ]);
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'tn_refund'],
        ]);

        $type = RoomType::create([
            'type_name' => 'Premier Suite',
            'price' => 1500000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
        ]);
        $room = Room::create([
            'room_number' => '501',
            'room_type_id' => $type->id,
            'floor' => 5,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        $completedBooking = Booking::create([
            'user_id' => $customer->id,
            'customer_name' => 'Nguyen Thi Lan',
            'customer_email' => 'lan-review@example.test',
            'customer_phone' => '0912000999',
            'check_in' => '2026-11-10',
            'check_out' => '2026-11-12',
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 3000000,
            'deposit_amount' => 1500000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);
        $completedBooking->rooms()->attach($room->id);

        $custSession = [
            'customer_user_id' => $customer->id,
            'user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
            'user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
        ];

        // Customer submits 3 consecutive reviews on the same completed booking without unique-constraint failure.
        for ($i = 1; $i <= 3; $i++) {
            $this->withSession($custSession)->actingAs($customer)
                ->get(route('reviews.create', ['booking_id' => $completedBooking->id, 'room_type_id' => $type->id]))
                ->assertOk();

            $this->withSession($custSession)->actingAs($customer)
                ->post(route('reviews.store'), [
                    'booking_id' => $completedBooking->id,
                    'room_type_id' => $type->id,
                    'rating' => 5,
                    'comment' => "Đánh giá lần thứ {$i} rất hài lòng.",
                ])->assertRedirect(route('rooms.detail', $type->id));
        }
        $this->assertSame(3, \App\Models\Review::where('booking_id', $completedBooking->id)->count());

        // Eligible cancellation displays bank fields, validates them, and shows them to Staff on /staff/cancellations.
        $refundableBooking = Booking::create([
            'user_id' => $customer->id,
            'customer_name' => 'Nguyen Thi Lan',
            'customer_email' => 'lan-review@example.test',
            'customer_phone' => '0912000999',
            'check_in' => now()->addDays(10)->toDateString(),
            'check_out' => now()->addDays(12)->toDateString(),
            'adult_count' => 2,
            'child_count' => 0,
            'total_price' => 3000000,
            'deposit_amount' => 1500000,
            'payment_method' => 'vietqr',
            'payment_status' => 'paid',
            'status' => 'confirmed',
        ]);
        $refundableBooking->rooms()->attach($room->id);

        $this->withSession($custSession)->actingAs($customer)
            ->get(route('booking.cancel.show', $refundableBooking->id))
            ->assertOk()
            ->assertSee('name="refund_bank_name"', false)
            ->assertSee('name="refund_account_number"', false)
            ->assertSee('name="refund_account_holder"', false);

        // Missing bank fields on eligible booking fails validation.
        $this->withSession($custSession)->actingAs($customer)
            ->post(route('booking.cancel', $refundableBooking->id), ['reason' => 'Đổi lịch công tác'])
            ->assertSessionHasErrors(['refund_bank_name', 'refund_account_number', 'refund_account_holder']);

        // Providing bank fields succeeds and stores normalized beneficiary details.
        $this->withSession($custSession)->actingAs($customer)
            ->post(route('booking.cancel', $refundableBooking->id), [
                'reason' => 'Đổi lịch công tác',
                'refund_bank_name' => 'Techcombank',
                'refund_account_number' => '1903 8888 9999',
                'refund_account_holder' => 'nguyen thi lan',
            ])->assertRedirect(route('booking.mine'));

        $this->assertSame('Techcombank', $refundableBooking->fresh()->refund_bank_name);
        $this->assertSame('190388889999', $refundableBooking->fresh()->refund_account_number);
        $this->assertSame('NGUYEN THI LAN', $refundableBooking->fresh()->refund_account_holder);

        $this->flushSession();

        // Staff sees the bank name, account number, and holder on /staff/cancellations!
        $this->withSession([
            'staff_user_id' => $staff->id,
            'user_id' => $staff->id,
            'staff_user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
            'user' => ['id' => $staff->id, 'role' => 'receptionist', 'verified' => true],
        ])->actingAs($staff)
            ->get(route('staff.cancellations'))
            ->assertOk()
            ->assertSee('Techcombank')
            ->assertSee('190388889999')
            ->assertSee('NGUYEN THI LAN');
    }
}
