<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\Role;
use App\Models\User;
use App\Models\Booking;
use Database\Seeders\RoomCapacitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_permission_page_uses_internal_accounts_and_database_roles(): void
    {
        $admin = $this->internalUser('admin', 'admin-ui@example.test');
        $staff = $this->internalUser('receptionist', 'staff-ui@example.test');
        $nightAudit = Role::query()->create(['slug' => 'night-audit', 'name' => 'Night audit']);
        $extra = User::query()->create([
            'username' => 'audit-ui@example.test',
            'fullname' => 'Night audit Test',
            'email' => 'audit-ui@example.test',
            'password' => Hash::make('123456'),
            'role' => 'receptionist',
            'role_id' => $nightAudit->id,
            'verified' => true,
        ]);
        $customer = $this->internalUser('customer', 'guest-ui@example.test');

        $this->asInternal($admin)->get(route('admin.roles.index'))
            ->assertOk()
            ->assertSee('staff-ui@example.test')
            ->assertSee('data-role="night-audit"', false)
            ->assertSee('data-role="night-audit">Night audit</button>', false)
            ->assertSee('/admin/roles/permissions?user_id=', false)
            ->assertDontSee('Quyền theo vai trò')
            ->assertDontSee('Lưu quyền cho role')
            ->assertSee('Night audit')
            ->assertDontSee('guest-ui@example.test')
            ->assertDontSee('letan_thao')
            ->assertDontSee('kh_view_contact')
            ->assertDontSee('tn_create_invoice');
    }

    public function test_report_permission_is_off_for_employee_roles_and_role_editor_updates_every_assigned_account(): void
    {
        $admin = $this->internalUser('admin', 'report-rbac-admin@example.test');
        $staff = $this->internalUser('receptionist', 'report-rbac-staff@example.test');
        $anotherStaff = $this->internalUser('receptionist', 'report-rbac-staff-2@example.test');
        $staffRole = $staff->assignedRole;
        $adminRole = Role::query()->where('slug', 'admin')->firstOrFail();

        $adminEditor = $this->asInternal($admin)->get(route('admin.roles.permissions.index', ['role_id' => $adminRole->id]))
            ->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/name="permissions\[\]" value="rp_view_reports"\s+checked\s+disabled/', $adminEditor);

        $staffEditor = $this->asInternal($admin)->get(route('admin.roles.permissions.index', ['role_id' => $staffRole->id]))
            ->assertOk()->getContent();
        $this->assertStringContainsString('Xem sơ đồ phòng và lịch đặt', $staffEditor);
        $this->assertDoesNotMatchRegularExpression('/value="rp_view_reports"[^>]*\bchecked\b/', $staffEditor);

        $this->asInternal($admin)->patch(route('admin.roles.role-permissions.update', $staffRole), [
            'permissions' => ['bp_view_schedule', 'rp_view_reports'],
            'user_id' => $staff->id,
        ])->assertRedirect(route('admin.roles.permissions.index', ['role_id' => $staffRole->id, 'user_id' => $staff->id]));

        foreach ([$staff, $anotherStaff] as $account) {
            $this->assertTrue($account->fresh()->hasInternalPermission('rp_view_reports'));
            $this->assertDatabaseMissing('user_permissions', [
                'user_id' => $account->id,
                'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER,
            ]);
        }

        $this->asInternal($staff)->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Doanh thu')
            ->assertSee('Báo cáo tổng hợp không hiển thị thông tin định danh khách hàng.')
            ->assertDontSee('Đặt phòng gần đây');
        $this->asInternal($admin)->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('Đặt phòng gần đây');
    }

    public function test_admin_report_keeps_three_original_charts_and_adds_three_analytics_charts(): void
    {
        $admin = $this->internalUser('admin', 'chart-admin@example.test');
        $type = RoomType::create([
            'type_name' => 'Chart test room',
            'price' => 120000,
            'max_adults' => 2,
            'max_children' => 0,
            'max_guests' => 2,
        ]);
        $room = Room::create([
            'room_number' => '881',
            'room_type_id' => $type->id,
            'floor' => 8,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        foreach (['confirmed', 'checked_in', 'completed'] as $status) {
            $bookingId = \Illuminate\Support\Facades\DB::table('bookings')->insertGetId([
                'customer_name' => 'Chart Guest',
                'customer_email' => 'chart-guest@example.test',
                'customer_phone' => '0900000000',
                'check_in' => '2025-02-10',
                'check_out' => '2025-02-11',
                'adult_count' => 1,
                'child_count' => 0,
                'total_price' => 120000,
                'payment_method' => 'vietqr',
                'payment_status' => 'paid',
                'status' => $status,
                'refund_status' => 'none',
                'refund_amount' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            \Illuminate\Support\Facades\DB::table('booking_rooms')->insert([
                'booking_id' => $bookingId,
                'room_id' => $room->id,
            ]);
        }

        $series = (new Booking())->getReportStatusTrendData([
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'room_type_id' => $type->id,
        ]);
        $this->assertCount(1, $series);
        $this->assertSame('2025-02', $series[0]->period);
        $this->assertSame(1, (int) $series[0]->confirmed);
        $this->assertSame(1, (int) $series[0]->checked_in);
        $this->assertSame(1, (int) $series[0]->completed);
        $weekdaySeries = (new Booking())->getReportWeekdayData([
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'room_type_id' => $type->id,
        ]);
        $this->assertCount(1, $weekdaySeries);
        $this->assertSame(1, (int) $weekdaySeries[0]->weekday);
        $this->assertSame(3, (int) $weekdaySeries[0]->bookings);

        $this->asInternal($admin)->get(route('admin.reports', [
            'start_date' => '2025-02-01',
            'end_date' => '2025-02-28',
            'room_type_id' => $type->id,
        ]))->assertOk()
            ->assertSee('trendChart')
            ->assertSee('roomTypeChart')
            ->assertSee('statusChart')
            ->assertSee('monthlyTrendChart')
            ->assertSee('statusTrendChart')
            ->assertSee('weekdayChart')
            ->assertSee("type: 'doughnut'", false)
            ->assertSee('Trạng thái đặt phòng theo tháng')
            ->assertSee("stack: 'booking-status'", false);
    }

    public function test_admin_can_save_a_separate_permission_set_for_one_internal_account(): void
    {
        $admin = $this->internalUser('admin', 'user-permission-admin@example.test');
        $staff = $this->internalUser('receptionist', 'user-permission-staff@example.test');
        $peer = $this->internalUser('receptionist', 'user-permission-peer@example.test');
        $staffRole = $staff->assignedRole;

        $this->asInternal($admin)->patch(route('admin.roles.user-permissions.update', $staff), [
            'permissions' => ['bp_view_schedule', 'bp_checkin_checkout', 'rp_view_reports'],
        ])->assertRedirect(route('admin.roles.index', ['user_id' => $staff->id]))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $staff->id,
            'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER,
        ]);
        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $staff->id,
            'permission_key' => 'rp_view_reports',
        ]);
        $this->assertTrue($staff->fresh()->hasInternalPermission('bp_checkin_checkout'));
        $this->assertTrue($staff->fresh()->hasInternalPermission('rp_view_reports'));
        $this->assertFalse($staff->fresh()->hasInternalPermission('tn_refund'));
        $this->assertFalse($peer->fresh()->hasInternalPermission('rp_view_reports'));

        $this->asInternal($admin)->get(route('admin.roles.index', ['user_id' => $staff->id]))
            ->assertOk()
            ->assertSee('Lưu quyền tài khoản')
            ->assertSee('Quyền riêng')
            ->assertSee('name="permissions[]" value="rp_view_reports" checked', false);

        $this->asInternal($admin)->patch(route('admin.roles.role-permissions.update', $staffRole), [
            'permissions' => ['bp_view_schedule', 'tn_collect_payment'],
        ]);
        $this->assertDatabaseHas('user_permissions', [
            'user_id' => $staff->id,
            'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER,
        ]);
        $this->assertTrue($staff->fresh()->hasInternalPermission('rp_view_reports'));
        $this->assertFalse($staff->fresh()->hasInternalPermission('tn_collect_payment'));
        $this->assertTrue($peer->fresh()->hasInternalPermission('tn_collect_payment'));
    }

    public function test_saved_permissions_are_checked_on_staff_routes(): void
    {
        $admin = $this->internalUser('admin', 'rbac-admin@example.test');
        $staff = $this->internalUser('receptionist', 'rbac-staff@example.test');

        $staffRole = $staff->assignedRole;
        $this->asInternal($admin)->patch(route('admin.roles.role-permissions.update', $staffRole), [
            'permissions' => ['bp_checkin_checkout'],
        ])->assertRedirect(route('admin.roles.permissions.index', ['role_id' => $staffRole->id]));

        $this->assertDatabaseMissing('user_permissions', [
            'user_id' => $staff->id,
            'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER,
        ]);
        $this->asInternal($staff)->get(route('staff.bookings'))->assertRedirect()->assertSessionHas('error');
        $this->asInternal($staff)->postJson(route('staff.reception.extend'), [])->assertForbidden();

        $this->asInternal($admin)->patch(route('admin.roles.role-permissions.update', $staffRole), [
            'permissions' => ['bp_view_schedule'],
        ]);
        $this->asInternal($staff)->get(route('staff.bookings'))->assertOk();
        $this->asInternal($staff)->postJson(route('staff.room.checkin', 999999))->assertForbidden();
    }

    public function test_room_schedule_permission_does_not_show_checkin_checkout_or_refund_actions(): void
    {
        $staff = $this->internalUser('receptionist', 'schedule-only@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'bp_view_schedule'],
        ]);

        $this->asInternal($staff)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee('id="room-grid-container"', false)
            ->assertDontSee('id="btn-action-occupied"', false)
            ->assertDontSee('id="btn-action-available"', false)
            ->assertDontSee('id="open-qr-scanner-btn"', false)
            ->assertDontSee('staff.cancellations', false);

        $this->asInternal($staff)->get(route('staff.cancellations'))
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_cleaning_and_extension_controls_follow_their_permissions(): void
    {
        $staff = $this->internalUser('receptionist', 'cleaning-extension@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'bp_view_schedule'],
            ['user_id' => $staff->id, 'permission_key' => 'bp_clean_status'],
            ['user_id' => $staff->id, 'permission_key' => 'bp_extend_stay'],
        ]);

        $this->asInternal($staff)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee('id="btn-action-cleaning-done"', false)
            ->assertSee('id="btn-action-cleaning-request-done"', false)
            ->assertSee('id="btn-action-extend"', false)
            ->assertDontSee('id="btn-action-occupied"', false)
            ->assertDontSee('id="btn-action-available"', false);
    }

    public function test_internal_login_lands_on_a_page_allowed_by_custom_role(): void
    {
        $staff = $this->internalUser('receptionist', 'refund-role-login@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'tn_refund'],
        ]);

        $this->post(route('internalauth.login'), [
            'username' => $staff->username,
            'password' => '123456',
        ])->assertRedirect(route('staff.cancellations'));
    }

    public function test_booking_creation_permission_uses_hold_flow_without_checkin_permission(): void
    {
        $staff = $this->internalUser('receptionist', 'booking-hold-only@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'bp_view_schedule'],
            ['user_id' => $staff->id, 'permission_key' => 'bp_create_booking'],
        ]);
        $type = RoomType::query()->create([
            'type_name' => 'Booking permission test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $room = Room::query()->create([
            'room_number' => 'P-101', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => Room::STATUS_AVAILABLE,
        ]);

        $this->asInternal($staff)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee('Tạo đặt phòng')
            ->assertSee('openWalkinCheckinModal(\'hold\')', false)
            ->assertDontSee('id="btn-action-occupied"', false)
            ->assertDontSee('id="btn-action-available"', false);

        $this->asInternal($staff)->postJson(route('staff.reception.walkin'), [
            'room_ids' => [$room->id],
            'customer_name' => 'Booking Permission Guest',
            'customer_phone' => '0912345678',
            'adult_count' => 1,
            'child_count' => 0,
            'walkin_type' => 'hold',
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
        ])->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('bookings', [
            'customer_name' => 'Booking Permission Guest',
            'status' => 'confirmed',
        ]);
        $this->assertSame(Room::STATUS_AVAILABLE, $room->fresh()->status);
    }

    public function test_checkin_permission_uses_checkin_endpoint_without_cleaning_permission(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(14, 0));
        $staff = $this->internalUser('receptionist', 'checkin-without-cleaning@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'bp_view_schedule'],
            ['user_id' => $staff->id, 'permission_key' => 'bp_checkin_checkout'],
        ]);
        $type = RoomType::query()->create([
            'type_name' => 'Check-in permission test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $room = Room::query()->create([
            'room_number' => 'CI-101', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => Room::STATUS_AVAILABLE,
        ]);
        $booking = Booking::query()->create([
            'customer_name' => 'Check-in Permission Guest',
            'customer_email' => 'checkin-permission-guest@example.test',
            'customer_phone' => '0912345678',
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'adult_count' => 1, 'child_count' => 0, 'total_price' => 10000,
            'payment_method' => 'cash', 'payment_status' => 'pending', 'status' => 'confirmed',
        ]);
        $booking->rooms()->attach($room->id);

        $this->asInternal($staff)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee('id="btn-action-occupied"', false);

        $this->asInternal($staff)->postJson(route('staff.room.checkin', $room->id))
            ->assertOk()->assertJsonPath('success', true);

        $this->assertSame('checked_in', $booking->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $room->fresh()->status);
        $this->travelBack();
    }

    public function test_partial_checkout_uses_checkout_permissions_and_keeps_booking_consistent(): void
    {
        $staffWithoutPayment = $this->internalUser('receptionist', 'partial-checkout-no-payment@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staffWithoutPayment->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staffWithoutPayment->id, 'permission_key' => 'bp_checkin_checkout'],
        ]);
        $type = RoomType::query()->create([
            'type_name' => 'Partial checkout test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $firstRoom = Room::query()->create([
            'room_number' => 'PC-101', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => Room::STATUS_OCCUPIED,
        ]);
        $lastRoom = Room::query()->create([
            'room_number' => 'PC-102', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => Room::STATUS_OCCUPIED,
        ]);
        $booking = Booking::query()->create([
            'customer_name' => 'Partial Checkout Guest',
            'customer_email' => 'partial-checkout-guest@example.test',
            'customer_phone' => '0912345678',
            'check_in' => now()->subDay()->toDateString(),
            'check_out' => now()->addDay()->toDateString(),
            'adult_count' => 2, 'child_count' => 0, 'total_price' => 20000,
            'payment_method' => 'cash', 'payment_status' => 'pending', 'status' => 'checked_in',
        ]);
        $booking->rooms()->attach([$firstRoom->id, $lastRoom->id]);

        $partialCheckoutData = [
            'room_id' => $firstRoom->id,
            'status' => 'cleaning',
            'checkout_scope' => 'room',
        ];
        $this->asInternal($staffWithoutPayment)
            ->postJson(route('staff.reception.update-status'), $partialCheckoutData)
            ->assertForbidden();
        $this->assertSame(Room::STATUS_OCCUPIED, $firstRoom->fresh()->status);

        $staffWithCheckout = $this->internalUser('receptionist', 'partial-checkout-authorized@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staffWithCheckout->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staffWithCheckout->id, 'permission_key' => 'bp_checkin_checkout'],
            ['user_id' => $staffWithCheckout->id, 'permission_key' => 'tn_collect_payment'],
        ]);

        $this->asInternal($staffWithCheckout)
            ->postJson(route('staff.reception.update-status'), $partialCheckoutData)
            ->assertOk()->assertJsonPath('success', true);
        $this->assertSame(Room::STATUS_CLEANING, $firstRoom->fresh()->status);
        $this->assertSame(Room::STATUS_OCCUPIED, $lastRoom->fresh()->status);
        $this->assertSame('checked_in', $booking->fresh()->status);

        $this->asInternal($staffWithCheckout)
            ->postJson(route('staff.reception.update-status'), [
                'room_id' => $lastRoom->id,
                'status' => 'cleaning',
                'checkout_scope' => 'room',
            ])
            ->assertUnprocessable();
        $this->assertSame(Room::STATUS_OCCUPIED, $lastRoom->fresh()->status);
        $this->assertSame('checked_in', $booking->fresh()->status);
    }

    public function test_checkout_success_page_requires_checkout_and_payment_permissions(): void
    {
        $staff = $this->internalUser('receptionist', 'checkout-page-checkin-only@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'bp_checkin_checkout'],
        ]);

        $this->asInternal($staff)->get(route('staff.bookings.checkout-success', 999999))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->asInternal($staff)->get(route('staff.bookings.vietqr', 999999))->assertRedirect();
        $this->asInternal($staff)->get(route('staff.bookings.check-status', 999999))->assertRedirect();

        $paymentOnly = $this->internalUser('receptionist', 'payment-without-checkout@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $paymentOnly->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $paymentOnly->id, 'permission_key' => 'tn_collect_payment'],
        ]);
        $this->asInternal($paymentOnly)->get(route('staff.bookings.check-status', 999999))->assertRedirect();
    }

    public function test_cleaning_permission_cannot_check_in_a_booking_through_room_status_endpoint(): void
    {
        $staff = $this->internalUser('receptionist', 'cleaning-only@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'bp_clean_status'],
        ]);

        $this->asInternal($staff)
            ->postJson(route('staff.reception.update-status'), [
                'room_id' => 999999,
                'status' => 'occupied',
            ])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseMissing('rooms', ['id' => 999999]);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_booking_creation_permission_does_not_grant_checkin_or_checkout_payment(): void
    {
        $staff = $this->internalUser('receptionist', 'booking-only@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $staff->id, 'permission_key' => 'bp_create_booking'],
        ]);

        $this->asInternal($staff)
            ->postJson(route('staff.reception.walkin'), ['walkin_type' => 'now'])
            ->assertForbidden();
        $this->asInternal($staff)
            ->postJson(route('staff.reception.quick-checkin'), [])
            ->assertForbidden();

        $this->asInternal($staff)
            ->postJson(route('staff.bookings.checkout-payment', 999999), ['payment_method' => 'cash'])
            ->assertForbidden();

        $checkinOnly = $this->internalUser('receptionist', 'checkin-only@example.test');
        \Illuminate\Support\Facades\DB::table('user_permissions')->insert([
            ['user_id' => $checkinOnly->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
            ['user_id' => $checkinOnly->id, 'permission_key' => 'bp_checkin_checkout'],
        ]);
        $this->asInternal($checkinOnly)
            ->postJson(route('staff.bookings.checkout-payment', 999999), ['payment_method' => 'cash'])
            ->assertForbidden();

        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_admin_cannot_confirm_an_expired_pending_payment_hold(): void
    {
        $admin = $this->internalUser('admin', 'expired-hold-admin@example.test');
        $customer = $this->internalUser('customer', 'expired-hold-guest@example.test');
        $booking = Booking::query()->create([
            'user_id' => $customer->id,
            'customer_name' => $customer->fullname,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'adult_count' => 1,
            'child_count' => 0,
            'total_price' => 10000,
            'deposit_amount' => 5000,
            'payment_method' => 'vietqr',
            'payment_status' => 'pending',
            'status' => 'pending',
        ]);
        \Illuminate\Support\Facades\DB::table('bookings')
            ->where('id', $booking->id)
            ->update(['updated_at' => now()->subMinutes(Booking::PAYMENT_HOLD_MINUTES + 1)]);

        $this->asInternal($admin)
            ->patch(route('staff.bookings.confirm', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('error', 'Thời hạn giữ phòng đã hết. Khách cần đặt lại phòng.');

        $this->assertSame('pending', $booking->fresh()->status);
    }

    public function test_admin_can_create_and_assign_a_database_role_with_permissions(): void
    {
        $admin = $this->internalUser('admin', 'profile-role-admin@example.test');
        $staff = $this->internalUser('receptionist', 'profile-role-staff@example.test');
        $anotherStaff = $this->internalUser('receptionist', 'profile-role-staff-2@example.test');

        $rolePage = $this->asInternal($admin)->get(route('admin.roles.index'));
        $rolePage->assertOk();
        foreach (array_keys(User::INTERNAL_PERMISSION_CATALOG) as $permissionKey) {
            $rolePage->assertSee('name="permissions[]" value="'.$permissionKey.'"', false);
        }

        $this->asInternal($admin)->post(route('admin.roles.profiles.store'), [
            'name' => 'Ca đêm',
            'permissions' => ['bp_view_schedule', 'bp_clean_status'],
        ])->assertRedirect()->assertSessionHas('success');

        $role = Role::where('slug', 'ca-dem')->firstOrFail();
        $this->assertSame('Ca đêm', $role->name);
        $this->assertEqualsCanonicalizing(['bp_view_schedule', 'bp_clean_status'], $role->permissionKeys());
        $this->asInternal($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('value="ca-dem"', false)
            ->assertSee('Ca đêm', false);

        foreach ([$staff, $anotherStaff] as $account) {
            $this->asInternal($admin)->patch(route('admin.roles.profile.assign', $account), [
                'profile' => $role->slug,
            ])->assertRedirect(route('admin.roles.index', ['user_id' => $account->id]));

            $this->assertSame($role->id, $account->fresh()->role_id);
            $this->assertTrue($account->fresh()->hasInternalPermission('bp_view_schedule'));
            $this->assertTrue($account->fresh()->hasInternalPermission('bp_clean_status'));
            $this->assertFalse($account->fresh()->hasInternalPermission('tn_refund'));
            $this->assertSame('receptionist', $account->fresh()->role);
        }

        $this->asInternal($admin)->get(route('admin.roles.index', ['user_id' => $staff->id]))
            ->assertOk()
            ->assertSee('Ca đêm')
            ->assertSee('Các quyền được chọn ban đầu theo vai trò')
            ->assertSee('Xem sơ đồ phòng và lịch đặt')
            ->assertDontSee('Vai trò quyền hạn')
            ->assertDontSee('Vai trò đăng nhập vẫn là')
            ->assertDontSee('Không có quyền xem hồ sơ, email, số điện thoại hoặc xuất dữ liệu khách hàng.');

        $roleList = $this->asInternal($admin)->get(route('admin.roles.permissions.index', ['role_id' => $role->id, 'user_id' => $staff->id]))
            ->assertOk()
            ->assertSee('Quay lại phân quyền')
            ->assertSee(route('admin.roles.index', ['user_id' => $staff->id]), false)
            ->assertSee('Ca đêm')
            ->assertSee('Lưu quyền cho role')
            ->assertSee('Xem sơ đồ phòng và lịch đặt');
        $this->assertMatchesRegularExpression('/name="permissions\[\]" value="bp_view_schedule"\s+checked/', $roleList->getContent());

        $this->asInternal($admin)->patch(route('admin.roles.role-permissions.update', $role), [
            'permissions' => ['bp_view_schedule'],
        ]);
        $this->assertDatabaseMissing('role_permissions', [
            'role_id' => $role->id,
            'permission_key' => 'bp_clean_status',
        ]);
        $this->assertTrue($anotherStaff->fresh()->hasInternalPermission('bp_view_schedule'));
        $this->assertFalse($anotherStaff->fresh()->hasInternalPermission('bp_clean_status'));
    }

    public function test_admin_can_create_an_internal_account_with_a_database_role(): void
    {
        Mail::fake();

        $admin = $this->internalUser('admin', 'role-assignment-admin@example.test');
        $page = $this->asInternal($admin)->get(route('admin.users.index'));
        $page->assertOk()
            ->assertSee('value="receptionist"', false)
            ->assertSee('value="admin"', false)
            ->assertSee('value="ke-toan"', false)
            ->assertSee('Kế toán', false)
            ->assertDontSee('name="permission_profile"', false);

        $this->asInternal($admin)->post(route('admin.users.store'), [
            'username' => 'accountant_staff',
            'fullname' => 'Nhân viên kế toán',
            'email' => 'accountant-staff@example.test',
            'phone' => '0900000000',
            'role' => 'ke-toan',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
        ])->assertRedirect(route('admin.users.index'))->assertSessionHas('success');
        Mail::assertSent(\App\Mail\ReceptionistAccountMail::class, fn ($mail) => $mail->roleLabel === 'Kế toán');

        $account = User::where('username', 'accountant_staff')->firstOrFail();
        $accountantRole = Role::where('slug', 'ke-toan')->firstOrFail();
        $this->assertSame('receptionist', $account->role);
        $this->assertSame($accountantRole->id, $account->role_id);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $accountantRole->id, 'permission_key' => 'tn_collect_payment']);
        $this->assertDatabaseHas('role_permissions', ['role_id' => $accountantRole->id, 'permission_key' => 'tn_refund']);
        $this->assertTrue($account->fresh()->hasInternalPermission('tn_refund'));
        $this->assertFalse($account->fresh()->hasInternalPermission('bp_checkin_checkout'));
        $permissionPage = $this->asInternal($admin)->get(route('admin.roles.index', ['user_id' => $account->id]));
        $permissionPage->assertOk()->assertSee('Kế toán');
        $permissionPage->assertSee(route('admin.roles.permissions.index', ['user_id' => $account->id]));
        $permissionPage->assertSee('name="permissions[]" value="tn_refund" checked', false);
        $this->asInternal($account)->get(route('receptionist.profile'))
            ->assertOk()
            ->assertSee('Kế toán');
        $rolePermissionPage = $this->asInternal($admin)->get(route('admin.roles.permissions.index', ['role_id' => $accountantRole->id]))
            ->assertOk();
        $this->assertMatchesRegularExpression(
            '/name="permissions\[\]" value="tn_refund"\s+checked/',
            $rolePermissionPage->getContent(),
        );
    }

    public function test_admin_can_update_internal_profile_and_staff_password(): void
    {
        Mail::fake();
        $admin = $this->internalUser('admin', 'profile-admin@example.test');
        $staff = $this->internalUser('receptionist', 'profile-staff@example.test');

        $this->asInternal($admin)->post(route('receptionist.profile.update-info'), [
            'fullname' => 'Admin Updated',
            'email' => '  ADMIN.UPDATED@example.test ',
            'phone' => '0912345678',
        ])->assertRedirect(route('receptionist.profile'));
        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'fullname' => 'Admin Updated',
            'email' => 'admin.updated@example.test',
            'phone' => '0912345678',
        ]);

        $this->asInternal($admin)->put(route('admin.users.update', $staff), [
            'username' => $staff->username,
            'fullname' => 'Staff Updated',
            'email' => 'staff.updated@example.test',
            'phone' => '0987654321',
            'role' => 'receptionist',
            'password' => '654321',
            'password_confirmation' => '654321',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'fullname' => 'Staff Updated',
            'email' => 'staff.updated@example.test',
        ]);
        $this->assertTrue(Hash::check('654321', $staff->fresh()->password));

        $this->asInternal($admin)->post(route('receptionist.profile.update-password'), [
            'current_password' => '123456',
            'new_password' => '654321',
            'new_password_confirmation' => '654321',
        ])->assertRedirect(route('receptionist.profile'));
        $this->assertTrue(Hash::check('654321', $admin->fresh()->password));
    }

    public function test_phone_and_money_fields_reject_letters_or_scientific_notation(): void
    {
        $admin = $this->internalUser('admin', 'numeric-validation-admin@example.test');

        $this->asInternal($admin)->post(route('admin.users.store'), [
            'username' => 'bad_phone_user',
            'fullname' => 'Invalid Phone',
            'email' => 'bad-phone-user@example.test',
            'phone' => '09012abc67',
            'role' => 'receptionist',
            'password' => 'abc123',
            'password_confirmation' => 'abc123',
        ])->assertSessionHasErrors('phone');
        $this->assertDatabaseMissing('users', ['email' => 'bad-phone-user@example.test']);

        $this->asInternal($admin)->post(route('receptionist.profile.update-info'), [
            'fullname' => $admin->fullname,
            'email' => $admin->email,
            'phone' => '09012abc67',
        ])->assertSessionHasErrors('phone');
        $this->assertSame('0912345678', $admin->fresh()->phone);

        $type = RoomType::query()->create([
            'type_name' => 'Numeric validation room', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $room = Room::query()->create([
            'room_number' => '101', 'room_type_id' => $type->id, 'floor' => 1, 'status' => 'available',
        ]);
        $this->asInternal($admin)->postJson(route('staff.reception.walkin'), [
            'room_ids' => [$room->id],
            'customer_name' => 'Invalid walk-in count',
            'customer_phone' => '0912345678',
            'adult_count' => '1e2',
            'child_count' => 0,
            'walkin_type' => 'hold',
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('adult_count');
        $this->assertDatabaseMissing('bookings', ['customer_name' => 'Invalid walk-in count']);

        $this->asInternal($admin)->postJson(route('staff.reception.walkin'), [
            'room_ids' => [$room->id],
            'customer_name' => 'Invalid walk-in phone',
            'customer_phone' => '09abc45678',
            'adult_count' => 1,
            'child_count' => 0,
            'walkin_type' => 'hold',
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
        ])->assertUnprocessable()->assertJsonValidationErrors('customer_phone');
        $this->assertDatabaseMissing('bookings', ['customer_name' => 'Invalid walk-in phone']);

        $this->asInternal($admin)->post(route('admin.room-types.store'), [
            'type_name' => 'Scientific price rejected',
            'price' => '1e4',
            'max_adults' => 1,
            'max_children' => 0,
            'max_guests' => 1,
            'amenities' => [],
        ])->assertSessionHasErrorsIn('addRoomType', 'price');
        $this->assertDatabaseMissing('room_types', ['type_name' => 'Scientific price rejected']);

        $this->asInternal($admin)->post(route('admin.price-settings.store'), [
            'name' => 'Invalid scientific adjustment',
            'start_date' => '2027-12-20',
            'end_date' => '2027-12-21',
            'adjustment_type' => 'percent',
            'adjustment_value' => '1e3',
        ])->assertSessionHasErrors('adjustment_value');
        $this->assertDatabaseMissing('price_settings', ['name' => 'Invalid scientific adjustment']);
    }

    public function test_admin_can_add_and_edit_rooms_and_room_types_without_delete_route(): void
    {
        $admin = $this->internalUser('admin', 'rooms-admin@example.test');
        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('Danh sách phòng')
            ->assertSee('Hạng phòng & Bảng giá niêm yết')
            ->assertSee('data-bs-target="#addRoomTypeModal"', false)
            ->assertSee('id="addRoomTypeModal"', false)
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('data-room-image-input', false)
            ->assertSee('data-room-image-preview', false)
            ->assertDontSee('<h2>Thêm hạng phòng</h2>', false);

        $type = RoomType::query()->create([
            'type_name' => 'Standard test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('data-bs-target="#editRoomTypeModal-'.$type->id.'"', false)
            ->assertSee('Chỉnh sửa')
            ->assertSee('macos-traffic-lights', false)
            ->assertSee('room-type-form__grid', false)
            ->assertSee('room-type-image-upload', false)
            ->assertSee('room-amenities', false)
            ->assertSee('Lưu hạng phòng')
            ->assertSee('.room-modal__body{flex:1 1 auto;min-height:0;overflow-y:auto', false)
            ->assertDontSee('<details class="room-type-card">', false);

        $this->asInternal($admin)->post(route('admin.room-types.store'), [
            'type_name' => 'Economy test', 'price' => 10000,
            'max_adults' => 1, 'max_children' => 0, 'max_guests' => 1,
            'amenities' => [], 'new_amenities' => 'Reading light',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('room_types', ['type_name' => 'Economy test', 'price' => 10000]);
        $this->assertDatabaseHas('amenities', ['amenity_name' => 'Reading light']);
        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('Economy test')
            ->assertSee('Reading light');

        $this->asInternal($admin)->post(route('admin.rooms.store'), [
            'room_number' => '10A', 'room_type_id' => $type->id, 'floor' => 1,
        ])->assertSessionHasErrorsIn('addRoom', 'room_number');
        $this->assertDatabaseMissing('rooms', ['room_number' => '10A']);

        $this->asInternal($admin)->post(route('admin.rooms.store'), [
            'room_number' => '201', 'room_type_id' => $type->id, 'floor' => 1,
        ])->assertSessionHasErrorsIn('addRoom', 'room_number');
        $this->assertDatabaseMissing('rooms', ['room_number' => '201']);

        $this->asInternal($admin)->post(route('admin.rooms.store'), [
            'room_number' => '601', 'room_type_id' => $type->id, 'floor' => 6,
        ])->assertRedirect()->assertSessionHas('success');
        $room = Room::query()->where('room_number', '601')->firstOrFail();
        $this->assertSame('available', $room->status);

        $newType = RoomType::query()->create([
            'type_name' => 'Deluxe test', 'price' => 20000,
            'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3,
        ]);
        $this->asInternal($admin)->patch(route('admin.rooms.update', $room), [
            'room_number' => '601', 'room_type_id' => $newType->id, 'floor' => 6,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame($newType->id, $room->fresh()->room_type_id);

        $booking = Booking::query()->create([
            'customer_name' => 'Test guest', 'customer_email' => 'guest@example.test',
            'customer_phone' => '0912345678', 'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(3)->toDateString(), 'adult_count' => 1,
            'child_count' => 0, 'total_price' => 20000, 'status' => 'confirmed', 'payment_status' => 'paid',
        ]);
        $booking->rooms()->attach($room->id);
        $this->asInternal($admin)->patch(route('admin.rooms.update', $room), [
            'room_number' => '601', 'room_type_id' => $type->id, 'floor' => 6,
        ])->assertSessionHasErrorsIn('editRoom'.$room->id, 'room_type_id');
        $this->assertSame($newType->id, $room->fresh()->room_type_id);

        $this->asInternal($admin)->patch(route('admin.room-types.update', $newType), [
            'type_name' => 'Deluxe updated', 'price' => 50000,
            'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3,
            'amenities' => [], 'new_amenities' => "Balcony\nCoffee machine",
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('room_types', ['id' => $newType->id, 'type_name' => 'Deluxe updated', 'price' => 50000]);
        $this->assertDatabaseHas('amenities', ['amenity_name' => 'Balcony']);
        $this->assertDatabaseHas('room_type_amenities', ['room_type_id' => $newType->id]);
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.rooms.destroy'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.room-types.destroy'));
    }

    public function test_room_type_capacity_is_validated_before_save(): void
    {
        $admin = $this->internalUser('admin', 'capacity-admin@example.test');

        $this->asInternal($admin)->post(route('admin.room-types.store'), [
            'type_name' => 'Invalid capacity', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 2, 'max_guests' => 3,
        ])->assertSessionHasErrorsIn('addRoomType', 'max_guests');

        $this->assertDatabaseMissing('room_types', ['type_name' => 'Invalid capacity']);

        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('id="addRoomTypeModal"', false)
            ->assertSee('bootstrap.Modal.getOrCreateInstance');

        $type = RoomType::query()->create([
            'type_name' => 'Capacity edit', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $editBag = 'editRoomType'.$type->id;
        $this->asInternal($admin)->patch(route('admin.room-types.update', $type), [
            'type_name' => 'Capacity edit attempted', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 2, 'max_guests' => 3,
        ])->assertSessionHasErrorsIn($editBag, 'max_guests');

        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('id="editRoomTypeModal-'.$type->id.'"', false)
            ->assertSee('value="Capacity edit attempted"', false);
        $this->assertDatabaseHas('room_types', ['id' => $type->id, 'type_name' => 'Capacity edit']);
    }

    public function test_admin_can_upload_and_replace_a_room_type_image(): void
    {
        Storage::fake('public');
        $admin = $this->internalUser('admin', 'room-image-admin@example.test');
        $payload = [
            'type_name' => 'Photo suite', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
            'amenities' => [],
        ];

        $this->asInternal($admin)->post(route('admin.room-types.store'), $payload + [
            'image_file' => UploadedFile::fake()->image('suite.jpg'),
        ])->assertRedirect()->assertSessionHas('success');

        $type = RoomType::query()->where('type_name', 'Photo suite')->firstOrFail();
        $this->assertStringStartsWith('storage/room-types/', $type->image);
        Storage::disk('public')->assertExists(substr($type->image, strlen('storage/')));

        $this->asInternal($admin)->patch(route('admin.room-types.update', $type), $payload + [
            'image_file' => UploadedFile::fake()->image('suite-updated.png'),
        ])->assertRedirect()->assertSessionHas('success');

        $updatedImage = $type->fresh()->image;
        $this->assertStringStartsWith('storage/room-types/', $updatedImage);
        Storage::disk('public')->assertExists(substr($updatedImage, strlen('storage/')));
        $this->assertNotSame($type->image, $updatedImage);
    }

    public function test_admin_can_add_up_to_three_uncropped_images_to_each_room_without_schema_columns(): void
    {
        Storage::fake('public');
        $admin = $this->internalUser('admin', 'room-gallery-admin@example.test');
        $type = RoomType::query()->create([
            'type_name' => 'Gallery test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
            'image' => 'images/rooms/category.jpg',
        ]);
        $room = Room::query()->create([
            'room_number' => '101', 'room_type_id' => $type->id,
            'floor' => 1, 'status' => 'available',
        ]);

        $this->asInternal($admin)->patch(route('admin.rooms.update', $room), [
            'room_number' => '101', 'room_type_id' => $type->id, 'floor' => 1,
            'images' => [
                UploadedFile::fake()->image('angle-one.png'),
                UploadedFile::fake()->image('angle-two.jpg'),
                UploadedFile::fake()->image('angle-three.webp'),
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $paths = $room->fresh()->galleryImagePaths();
        $this->assertCount(3, $paths);
        foreach ($paths as $path) {
            Storage::disk('public')->assertExists($path);
        }
        $this->assertSame(Storage::disk('public')->url($paths[0]), $room->fresh()->image_url);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('rooms', 'images'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('rooms', 'image'));

        $this->asInternal($admin)->patch(route('admin.rooms.update', $room), [
            'room_number' => '101', 'room_type_id' => $type->id, 'floor' => 1,
            'images' => [UploadedFile::fake()->image('excess.png')],
        ])->assertSessionHasErrorsIn('editRoom'.$room->id, 'images');
        $this->assertCount(3, $room->fresh()->galleryImagePaths());

        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('editRoomModal-'.$room->id, false)
            ->assertSee('object-fit:contain', false)
            ->assertSee('Tối đa 3 ảnh · 5 MB/ảnh');
        $this->asInternal($admin)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee('room-galleries');
    }

    public function test_admin_room_number_and_floor_changes_are_read_by_staff_and_customer_views(): void
    {
        $admin = $this->internalUser('admin', 'room-sync-admin@example.test');
        $type = RoomType::create([
            'type_name' => 'Room sync test',
            'price' => 200000,
            'max_adults' => 2,
            'max_children' => 0,
            'max_guests' => 2,
        ]);
        $room = Room::create([
            'room_number' => '101',
            'room_type_id' => $type->id,
            'floor' => 1,
            'status' => Room::STATUS_AVAILABLE,
        ]);

        $this->asInternal($admin)->patch(route('admin.rooms.update', $room), [
            'room_number' => '202',
            'room_type_id' => $type->id,
            'floor' => 2,
        ])->assertRedirect(route('admin.rooms.index', ['floor' => 2]));

        $this->assertDatabaseHas('rooms', [
            'id' => $room->id,
            'room_number' => '202',
            'floor' => 2,
        ]);

        $this->asInternal($admin)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee('202')
            ->assertSee('Tầng 2');

        $checkIn = now('Asia/Ho_Chi_Minh')->addDay()->toDateString();
        $checkOut = now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString();
        $this->get(route('rooms.search', [
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 1,
            'children' => 0,
        ]))->assertOk()
            ->assertSee('P.202')
            ->assertSee('Tầng 2');
    }

    public function test_admin_room_list_only_shows_selected_floor_and_rejects_floors_above_ten(): void
    {
        $admin = $this->internalUser('admin', 'floor-admin@example.test');
        $type = RoomType::query()->create([
            'type_name' => 'Floor test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        Room::query()->create(['room_number' => '101', 'room_type_id' => $type->id, 'floor' => 1, 'status' => 'available']);
        Room::query()->create(['room_number' => '201', 'room_type_id' => $type->id, 'floor' => 2, 'status' => 'available']);

        $this->asInternal($admin)->get(route('admin.rooms.index', ['floor' => 2]))
            ->assertOk()
            ->assertSee('201')
            ->assertDontSee('101')
            ->assertDontSee('2 phòng · 2 tầng trong dữ liệu')
            ->assertDontSee('Tổng số phòng')
            ->assertDontSee('Tầng đang chọn')
            ->assertDontSee('Hạng phòng khai thác')
            ->assertDontSee('<th style="width: 180px;">Ảnh riêng</th>', false)
            ->assertSee(route('admin.rooms.index', ['floor' => 2]), false)
            ->assertDontSee(route('admin.rooms.index', ['floor' => 10]), false);

        $this->asInternal($admin)->post(route('admin.rooms.store'), [
            'room_number' => 'QA-F11', 'room_type_id' => $type->id, 'floor' => 11,
        ])->assertSessionHasErrorsIn('addRoom', 'floor');

        $this->asInternal($admin)->get(route('admin.rooms.index', ['floor' => 2]))
            ->assertOk()
            ->assertSee('new-room-floor-error')
            ->assertSee('bootstrap.Modal.getOrCreateInstance');

        $this->assertDatabaseMissing('rooms', ['room_number' => 'QA-F11']);
    }

    public function test_admin_room_list_marks_a_confirmed_arrival_as_booked_before_checkin(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-04 14:00:00', 'Asia/Ho_Chi_Minh'));
        $admin = $this->internalUser('admin', 'arrival-admin@example.test');
        $customer = $this->internalUser('customer', 'arrival-guest@example.test');
        $type = RoomType::query()->create([
            'type_name' => 'Arrival test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        $room = Room::query()->create(['room_number' => '101', 'room_type_id' => $type->id, 'floor' => 1, 'status' => 'available']);
        $booking = Booking::query()->create([
            'user_id' => $customer->id, 'customer_name' => $customer->fullname,
            'customer_email' => $customer->email, 'customer_phone' => $customer->phone,
            'check_in' => '2026-10-04', 'check_out' => '2026-10-05',
            'adult_count' => 1, 'child_count' => 0, 'total_price' => 10000,
            'payment_status' => 'paid', 'status' => 'confirmed',
        ]);
        $booking->rooms()->attach($room->id);

        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('<span class="apple-status-pill apple-status-pill--reserved">', false)
            ->assertSee('Đã đặt');
        $booking->update(['status' => 'cancelled']);
        $this->asInternal($admin)->get(route('admin.rooms.index'))
            ->assertOk()
            ->assertSee('<span class="apple-status-pill apple-status-pill--available">', false)
            ->assertSee('Trống');
        $this->travelBack();
    }

    public function test_room_capacity_seeder_completes_ten_floors_with_five_rooms_each(): void
    {
        $type = RoomType::query()->create([
            'type_name' => 'Seeder test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);

        foreach (range(1, 5) as $floor) {
            foreach (range(1, 5) as $slot) {
                Room::query()->create([
                    'room_number' => (string) ($floor * 100 + $slot),
                    'room_type_id' => $type->id,
                    'floor' => $floor,
                    'status' => 'available',
                ]);
            }
        }

        $this->seed(RoomCapacitySeeder::class);

        $this->assertSame(10, Room::query()->distinct()->count('floor'));
        foreach (range(1, 10) as $floor) {
            $this->assertSame(5, Room::query()->where('floor', $floor)->count());
            foreach (range(1, 5) as $slot) {
                $this->assertDatabaseHas('rooms', [
                    'floor' => $floor,
                    'room_number' => (string) ($floor * 100 + $slot),
                ]);
            }
        }
    }

    public function test_admin_navigation_remains_available_on_the_room_board(): void
    {
        $admin = $this->internalUser('admin', 'navigation-admin@example.test');
        $type = RoomType::query()->create([
            'type_name' => 'Map test', 'price' => 10000,
            'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2,
        ]);
        Room::query()->create(['room_number' => '601', 'room_type_id' => $type->id, 'floor' => 6, 'status' => 'available']);

        $this->asInternal($admin)->get(route('staff.bookings'))
            ->assertOk()
            ->assertSee(route('admin.rooms.index'), false)
            ->assertSee('Phòng &amp; hạng phòng', false)
            ->assertSee('Tầng 6')
            ->assertSee('601')
            ->assertSee('data-floor="6"', false);
    }

    private function internalUser(string $role, string $email): User
    {
        return User::query()->create([
            'username' => $email,
            'fullname' => ucfirst($role).' Test',
            'email' => $email,
            'phone' => '0912345678',
            'password' => Hash::make('123456'),
            'role' => $role,
            'verified' => true,
        ]);
    }

    private function asInternal(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'staff_user_id' => $user->id,
            'auth_user_id' => $user->id,
            'user_id' => $user->id,
            'user' => ['id' => $user->id, 'role' => $user->role, 'fullname' => $user->fullname, 'verified' => true],
        ]);
    }
}
