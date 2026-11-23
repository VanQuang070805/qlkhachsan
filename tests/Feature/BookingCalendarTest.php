<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingCalendarTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_search_the_booking_calendar_by_room_number(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Ho_Chi_Minh'));
        [$booking] = $this->bookingWithRoom('101', 'Nguyễn Minh Anh', 'confirmed');
        $admin = $this->internalUser('calendar-admin@example.test', 'admin');

        $this->asInternal($admin)
            ->get(route('staff.booking-calendar', ['q' => '101']))
            ->assertOk()
            ->assertSee('Lịch đặt phòng')
            ->assertSee('Nguyễn Minh Anh')
            ->assertSee('Check-in sớm')
            ->assertSee(route('staff.bookings.early-checkin', $booking->id), false);

        $this->travelBack();
    }

    public function test_authorized_staff_can_check_in_early_when_every_room_is_available(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 09:30:00', 'Asia/Ho_Chi_Minh'));
        [$booking, $room] = $this->bookingWithRoom('102', 'Trần Thu Hà', 'confirmed');
        $staff = $this->internalUser('calendar-staff@example.test', 'receptionist');

        $this->asInternal($staff)
            ->patch(route('staff.bookings.early-checkin', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('checked_in', $booking->fresh()->status);
        $this->assertNotNull($booking->fresh()->actual_check_in);
        $this->assertSame(Room::STATUS_OCCUPIED, $room->fresh()->status);
        $this->travelBack();
    }

    public function test_early_check_in_still_rejects_a_booking_before_its_arrival_date(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 09:30:00', 'Asia/Ho_Chi_Minh'));
        [$booking] = $this->bookingWithRoom('103', 'Lê Quốc Bảo', 'confirmed', '2026-11-21');
        $staff = $this->internalUser('future-checkin@example.test', 'receptionist');

        $this->asInternal($staff)
            ->patch(route('staff.bookings.early-checkin', $booking->id))
            ->assertRedirect()
            ->assertSessionHas('error', 'Booking chưa đến ngày nhận phòng.');

        $this->assertSame('confirmed', $booking->fresh()->status);
        $this->travelBack();
    }

    public function test_booking_detail_exposes_edit_cancel_and_early_checkin_actions(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Ho_Chi_Minh'));
        [$booking] = $this->bookingWithRoom('104', 'Đỗ Ngọc Lan', 'confirmed');
        $staff = $this->internalUser('detail-staff@example.test', 'receptionist');

        $this->asInternal($staff)
            ->get(route('staff.booking-calendar.show', $booking))
            ->assertOk()
            ->assertSee('Thông tin đặt phòng')
            ->assertSee('Check-in sớm')
            ->assertSee('Lưu thay đổi')
            ->assertSee('Xóa booking');

        $this->travelBack();
    }

    public function test_staff_can_edit_an_unpaid_booking_and_change_its_room(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Ho_Chi_Minh'));
        [$booking, $firstRoom] = $this->bookingWithRoom('105', 'Khách cũ', 'confirmed', '2026-11-20', 'pending');
        $secondRoom = Room::create([
            'room_number' => '106',
            'room_type_id' => $firstRoom->room_type_id,
            'floor' => 1,
            'status' => Room::STATUS_AVAILABLE,
        ]);
        $staff = $this->internalUser('edit-staff@example.test', 'receptionist');

        $this->asInternal($staff)->patch(route('staff.booking-calendar.update', $booking), [
            'customer_name' => 'Khách đã sửa',
            'customer_email' => 'updated@example.test',
            'customer_phone' => '0987654321',
            'room_ids' => [$secondRoom->id],
            'check_in' => '2026-11-20',
            'check_out' => '2026-11-22',
            'adult_count' => 2,
            'child_count' => 0,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame('Khách đã sửa', $booking->fresh()->customer_name);
        $this->assertEqualsCanonicalizing([$secondRoom->id], $booking->fresh()->rooms()->pluck('rooms.id')->all());
        $this->travelBack();
    }

    public function test_delete_action_cancels_the_booking_without_removing_its_record(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Ho_Chi_Minh'));
        [$booking] = $this->bookingWithRoom('107', 'Khách hủy', 'confirmed', '2026-11-21', 'pending');
        $staff = $this->internalUser('cancel-staff@example.test', 'receptionist');

        $this->asInternal($staff)
            ->delete(route('staff.booking-calendar.destroy', $booking), ['reason' => 'Khách đổi lịch'])
            ->assertRedirect(route('staff.booking-calendar'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Khách đổi lịch',
        ]);
        $this->travelBack();
    }

    public function test_notification_feed_combines_new_bookings_and_cleaning_requests(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Ho_Chi_Minh'));
        [$booking, $room] = $this->bookingWithRoom('108', 'Khách thông báo', 'confirmed');
        $room->update(['needs_cleaning' => true, 'cleaning_requested_at' => now('Asia/Ho_Chi_Minh')]);
        $staff = $this->internalUser('notification-staff@example.test', 'receptionist');

        $this->asInternal($staff)
            ->getJson(route('staff.notifications.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonFragment(['title' => "Booking mới #{$booking->id}"])
            ->assertJsonFragment(['title' => 'Phòng 108 cần dọn']);

        $this->postJson(route('staff.notifications.read'))->assertOk();
        $this->getJson(route('staff.notifications.index'))->assertJsonPath('unread_count', 1);
        $this->travelBack();
    }

    public function test_admin_operational_routes_use_dedicated_admin_prefix_and_block_receptionists(): void
    {
        $this->travelTo(Carbon::parse('2026-11-20 10:00:00', 'Asia/Ho_Chi_Minh'));
        [$booking] = $this->bookingWithRoom('202', 'Phạm Hoàng Nam', 'confirmed');
        $admin = $this->internalUser('portal-admin@example.test', 'admin');
        $staff = $this->internalUser('portal-staff@example.test', 'receptionist');

        // Admin accesses Sơ đồ phòng under /admin/bookings and all internal actions use /admin/*
        $this->asInternal($admin)
            ->get(route('admin.bookings'))
            ->assertOk()
            ->assertSee(route('admin.cancellations'), false)
            ->assertSee(route('admin.reception.booking-by-scan'), false)
            ->assertSee(route('admin.reception.walkin'), false)
            ->assertDontSee('href="'.route('staff.bookings').'"', false)
            ->assertDontSee('href="'.route('staff.booking-calendar').'"', false);

        // Admin accesses Lịch đặt phòng under /admin/booking-calendar
        $this->asInternal($admin)
            ->get(route('admin.booking-calendar', ['q' => '202']))
            ->assertOk()
            ->assertSee('Phạm Hoàng Nam')
            ->assertSee(route('admin.booking-calendar.show', $booking), false)
            ->assertSee(route('admin.bookings.early-checkin', $booking->id), false);

        // Admin accesses Chi tiết lịch đặt phòng under /admin/booking-calendar/{booking}
        $this->asInternal($admin)
            ->get(route('admin.booking-calendar.show', $booking))
            ->assertOk()
            ->assertSee(route('admin.booking-calendar.update', $booking), false)
            ->assertSee(route('admin.booking-calendar.destroy', $booking), false);

        // Admin accesses Face ID & IoT, Hủy phòng & hoàn tiền, Hồ sơ cá nhân under /admin/*
        $this->asInternal($admin)->get(route('admin.iot'))->assertOk()->assertSee(route('admin.face-id.recognize'), false);
        $this->asInternal($admin)->get(route('admin.cancellations'))->assertOk();
        $this->asInternal($admin)->get(route('admin.profile'))->assertOk()->assertSee(route('admin.profile.update-info'), false);

        // Admin notification feed generates /admin/* destination links
        $this->asInternal($admin)
            ->getJson(route('admin.notifications.index'))
            ->assertOk()
            ->assertJsonFragment(['url' => route('admin.booking-calendar.show', $booking)]);

        // Receptionist cannot access /admin/bookings or /admin/booking-calendar
        $this->asInternal($staff)->get(route('admin.bookings'))->assertRedirect();
        $this->asInternal($staff)->get(route('admin.booking-calendar'))->assertRedirect();

        $this->travelBack();
    }

    private function bookingWithRoom(string $roomNumber, string $guestName, string $status, string $checkIn = '2026-11-20', string $paymentStatus = 'paid'): array
    {
        $type = RoomType::create([
            'type_name' => 'Calendar '.uniqid(),
            'price' => 600000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
        ]);
        $room = Room::create([
            'room_number' => $roomNumber,
            'room_type_id' => $type->id,
            'floor' => 1,
            'status' => Room::STATUS_AVAILABLE,
        ]);
        $booking = Booking::create([
            'customer_name' => $guestName,
            'customer_email' => 'guest-'.uniqid().'@example.test',
            'customer_phone' => '0912345678',
            'check_in' => $checkIn,
            'check_out' => Carbon::parse($checkIn)->addDays(2)->toDateString(),
            'adult_count' => 1,
            'child_count' => 0,
            'total_price' => 1200000,
            'payment_method' => 'momo',
            'payment_status' => $paymentStatus,
            'status' => $status,
        ]);
        $booking->rooms()->attach($room->id);

        return [$booking, $room];
    }

    private function internalUser(string $email, string $role): User
    {
        $user = User::create([
            'username' => strstr($email, '@', true),
            'fullname' => 'Calendar Operator',
            'email' => $email,
            'phone' => '0900000000',
            'password' => Hash::make('Password123!'),
            'role' => $role,
            'verified' => true,
        ]);

        if ($role === 'receptionist') {
            DB::table('user_permissions')->insert([
                ['user_id' => $user->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER],
                ['user_id' => $user->id, 'permission_key' => 'bp_view_schedule'],
                ['user_id' => $user->id, 'permission_key' => 'bp_create_booking'],
                ['user_id' => $user->id, 'permission_key' => 'bp_checkin_checkout'],
            ]);
        }

        return $user;
    }

    private function asInternal(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'staff_user_id' => $user->id,
            'staff_user' => ['id' => $user->id, 'role' => $user->role, 'verified' => true],
        ]);
    }
}
