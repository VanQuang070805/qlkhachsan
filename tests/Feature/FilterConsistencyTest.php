<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\PaymentLog;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FilterConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_does_not_claim_unsent_inquiries_were_received(): void
    {
        $this->get(route('contact'))->assertOk()
            ->assertSee('Biểu mẫu đang chưa được kết nối')
            ->assertSee('<fieldset disabled', false)
            ->assertDontSee('đã tiếp nhận thông tin và sẽ liên hệ lại');
    }

    public function test_booking_history_filters_before_pagination_and_combines_status_with_search(): void
    {
        $customer = $this->customer();
        $type = RoomType::create(['type_name' => 'Deluxe', 'price' => 10000, 'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3]);
        $room = Room::create(['room_number' => 'FILTER-101', 'floor' => 1, 'room_type_id' => $type->id, 'status' => 'available']);

        for ($i = 0; $i < 11; $i++) {
            $this->booking($customer, 'completed');
        }
        $cancelled = $this->booking($customer, 'cancelled');
        $cancelled->rooms()->attach($room->id);

        $this->asCustomer($customer)
            ->get(route('booking.mine', ['status' => 'cancelled', 'q' => 'FILTER-101']))
            ->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 1 && $bookings->first()->id === $cancelled->id);

        $this->asCustomer($customer)
            ->get(route('booking.mine', ['status' => 'completed', 'q' => 'FILTER-101']))
            ->assertOk()
            ->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 0);
    }

    public function test_room_search_requires_an_adult_and_homepage_links_to_room_search(): void
    {
        $this->travelTo(now('Asia/Ho_Chi_Minh')->setTime(10, 0));

        $this->get(route('home'))->assertOk()
            ->assertSee('href="'.route('rooms.index').'"', false);

        $this->get(route('rooms.index', [
            'search' => 1,
            'check_in' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString(),
            'adults' => 0,
            'children' => 0,
        ]))->assertSessionHasErrors('adults');
    }

    public function test_booking_history_refund_details_use_recorded_amount_and_status(): void
    {
        $customer = $this->customer();
        $booking = $this->booking($customer, 'cancelled');
        $booking->update(['refund_status' => 'eligible', 'refund_amount' => 3000]);
        $this->asCustomer($customer)->get(route('booking.mine', ['status' => 'cancelled']))
            ->assertOk()->assertSee('data-refund-status="eligible"', false)
            ->assertSee('data-refund-amount="3.000"', false)
            ->assertDontSee('ROSA-REFUND-7458291048')
            ->assertDontSee('Đã ghi có vào tài khoản')
            ->assertDontSee('0008492');
    }

    public function test_booking_success_identifies_deposit_and_does_not_claim_wallet_was_added(): void
    {
        $customer = $this->customer();
        $booking = $this->booking($customer, 'confirmed');
        $booking->update(['payment_status' => 'paid']);
        $this->asCustomer($customer)->get(route('booking.success', $booking->id))
            ->assertOk()->assertSee('Đã thanh toán cọc')
            ->assertDontSee('Thêm thẻ phòng vào Apple Wallet')
            ->assertDontSee('Đã thêm vào Apple Wallet');
    }

    public function test_report_revenue_uses_received_deposit_instead_of_full_booking_price(): void
    {
        $booking = $this->booking($this->customer(), 'confirmed');
        $booking->update(['payment_status' => 'paid']);
        $filters = ['start_date' => '', 'end_date' => '', 'room_type_id' => '', 'status' => ''];

        $this->assertSame(5000.0, (new Booking)->getReportStats($filters)['total_revenue']);
        $this->assertSame(5000.0, (float) (new Booking)->getReportChartData($filters)[0]->revenue);
        $this->assertSame(5000.0, (float) (new Booking)->getFilteredBookings($filters)[0]->received_amount);

        PaymentLog::create([
            'booking_id' => $booking->id, 'gateway' => 'vietqr', 'purpose' => 'deposit',
            'reference_code' => 'FILTER-PAID-1', 'amount' => 6000, 'status' => 'success',
        ]);
        $this->assertSame(6000.0, (new Booking)->getReportStats($filters)['total_revenue']);
    }

    public function test_report_sold_nights_counts_rooms_and_excludes_pending_or_cancelled_bookings(): void
    {
        $customer = $this->customer();
        $type = RoomType::create(['type_name' => 'Room nights', 'price' => 10000, 'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2]);
        $rooms = collect([1, 2])->map(fn (int $slot) => Room::create([
            'room_number' => 'NIGHT-'.$slot, 'floor' => 1, 'room_type_id' => $type->id, 'status' => 'available',
        ]));

        $confirmed = $this->booking($customer, 'confirmed');
        $confirmed->update(['check_in' => '2026-06-10', 'check_out' => '2026-06-12']);
        $confirmed->rooms()->attach($rooms->pluck('id'));

        foreach (['pending', 'cancelled'] as $status) {
            $ignored = $this->booking($customer, $status);
            $ignored->update(['check_in' => '2026-06-10', 'check_out' => '2026-06-12']);
            $ignored->rooms()->attach($rooms->first()->id);
        }

        $stats = (new Booking)->getReportStats([
            'start_date' => '', 'end_date' => '', 'room_type_id' => '', 'status' => '',
        ]);

        $this->assertSame(4, $stats['total_nights']);
    }

    public function test_report_status_filter_applies_to_metrics_chart_and_table(): void
    {
        $customer = $this->customer();
        $this->booking($customer, 'confirmed');
        $this->booking($customer, 'completed');
        $admin = User::create([
            'username' => 'filter-admin', 'fullname' => 'Filter Admin',
            'email' => 'filter-admin@example.com', 'phone' => '0900000000',
            'password' => Hash::make('password123'), 'role' => 'admin', 'verified' => true,
        ]);

        $this->actingAs($admin)->withSession([
            'staff_user_id' => $admin->id,
            'staff_user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
        ])->get(route('admin.reports', ['status' => 'completed']))
            ->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['total_bookings'] === 1)
            ->assertViewHas('bookings', fn ($bookings) => count($bookings) === 1 && $bookings[0]->status === 'completed')
            ->assertViewHas('chartData', fn ($rows) => array_sum(array_column($rows, 'bookings')) === 1);
    }

    public function test_report_compares_equal_periods_from_filtered_booking_data(): void
    {
        $customer = $this->customer();
        $first = $this->booking($customer, 'confirmed');
        $first->update(['check_in' => '2026-04-10', 'check_out' => '2026-04-11']);
        $second = $this->booking($customer, 'confirmed');
        $second->update(['check_in' => '2026-04-10', 'check_out' => '2026-04-11']);
        $previous = $this->booking($customer, 'confirmed');
        $previous->update(['check_in' => '2026-04-09', 'check_out' => '2026-04-10']);
        $admin = User::create([
            'username' => 'compare-admin', 'fullname' => 'Compare Admin',
            'email' => 'compare-admin@example.com', 'phone' => '0900000000',
            'password' => Hash::make('password123'), 'role' => 'admin', 'verified' => true,
        ]);
        $this->actingAs($admin)->withSession(['staff_user_id' => $admin->id])
            ->get(route('admin.reports', ['start_date' => '2026-04-10', 'end_date' => '2026-04-10', 'status' => 'confirmed']))
            ->assertOk()
            ->assertViewHas('comparison', fn ($data) => $data['total_bookings']['current'] === 2.0
                && $data['total_bookings']['previous'] === 1.0 && $data['total_bookings']['percent'] === 100.0)
            ->assertSee('100,0%')->assertSee('bi-graph-up-arrow', false);
        $this->get(route('admin.reports', ['start_date' => '2026-04-11', 'end_date' => '2026-04-11', 'status' => 'confirmed']))
            ->assertOk()->assertSee('100,0%')->assertSee('bi-graph-down-arrow', false);
        $this->get(route('admin.reports', ['start_date' => '2026-04-09', 'end_date' => '2026-04-09', 'status' => 'confirmed']))
            ->assertOk()->assertSee('Mới phát sinh');
    }

    public function test_staff_report_filter_stays_on_staff_portal(): void
    {
        $staff = User::create([
            'username' => 'report-filter-staff', 'fullname' => 'Report Staff',
            'email' => 'report-filter-staff@example.com', 'phone' => '0900000000',
            'password' => Hash::make('password123'), 'role' => 'receptionist', 'verified' => true,
        ]);
        \DB::table('user_permissions')->insert([
            ['user_id' => $staff->id, 'permission_key' => User::PERMISSIONS_CONFIGURED_MARKER, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => $staff->id, 'permission_key' => 'rp_view_reports', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->actingAs($staff)->withSession(['staff_user_id' => $staff->id])
            ->get('http://127.0.0.1:18081/staff/reports?status=completed')
            ->assertOk()->assertSee('action="http://127.0.0.1:18081/staff/reports"', false)
            ->assertDontSee('action="http://127.0.0.1:18081/admin/reports"', false);
    }

    public function test_staff_room_search_indexes_the_active_booking_id(): void
    {
        $customer = $this->customer();
        $type = RoomType::create(['type_name' => 'Staff Filter Room', 'price' => 10000, 'max_adults' => 2, 'max_children' => 0, 'max_guests' => 2]);
        $room = Room::create(['room_number' => 'FILTER-101', 'floor' => 1, 'room_type_id' => $type->id, 'status' => 'occupied']);
        $booking = $this->booking($customer, 'checked_in');
        $booking->update(['check_in' => now()->subDay()->toDateString(), 'check_out' => now()->addDay()->toDateString()]);
        $booking->rooms()->attach($room->id);
        $admin = User::create([
            'username' => 'filter-staff-admin', 'fullname' => 'Filter Staff Admin',
            'email' => 'filter-staff-admin@example.com', 'phone' => '0900000000',
            'password' => Hash::make('password123'), 'role' => 'admin', 'verified' => true,
        ]);

        $html = $this->actingAs($admin)->withSession([
            'staff_user_id' => $admin->id,
            'staff_user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
        ])->get(route('staff.bookings'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-search="[^"]*filter-101[^"]*\b'.$booking->id.'\b[^"]*"/', $html);
        $this->assertStringContainsString('class="staff-floor-nav"', $html);
        $this->assertStringContainsString('data-floor-tab="1"', $html);
    }

    public function test_admin_role_filter_includes_legacy_accounts_without_role_id(): void
    {
        $customer = $this->customer();
        \DB::table('users')->where('id', $customer->id)->update(['role_id' => null]);
        $admin = User::create([
            'username' => 'legacy-filter-admin', 'fullname' => 'Legacy Filter Admin',
            'email' => 'legacy-filter-admin@example.com', 'phone' => '0900000000',
            'password' => Hash::make('password123'), 'role' => 'admin', 'verified' => true,
        ]);
        $this->actingAs($admin)->withSession(['staff_user_id' => $admin->id])
            ->get(route('admin.users.index', ['role' => 'customer']))
            ->assertOk()->assertViewHas('users', fn ($users) => $users->contains('id', $customer->id));
    }

    public function test_cancelled_bookings_without_refunds_have_a_working_filter(): void
    {
        $booking = $this->booking($this->customer(), 'cancelled');
        $booking->update(['refund_status' => 'none']);
        $admin = User::create([
            'username' => 'filter-refund-admin', 'fullname' => 'Filter Refund Admin',
            'email' => 'filter-refund-admin@example.com', 'phone' => '0900000000',
            'password' => Hash::make('password123'), 'role' => 'admin', 'verified' => true,
        ]);

        $this->actingAs($admin)->withSession([
            'staff_user_id' => $admin->id,
            'staff_user' => ['id' => $admin->id, 'role' => 'admin', 'verified' => true],
        ])->get(route('staff.cancellations'))->assertOk()
            ->assertSee('data-cancellation-status', false)
            ->assertSee('data-cancellation-record data-status="none"', false)
            ->assertSee('#'.$booking->id);
    }

    private function customer(): User
    {
        return User::create([
            'username' => 'filter-customer@example.com', 'fullname' => 'Filter Guest',
            'email' => 'filter-customer@example.com', 'phone' => '0912345678',
            'password' => Hash::make('password123'), 'role' => 'customer', 'verified' => true,
        ]);
    }

    private function booking(User $customer, string $status): Booking
    {
        return Booking::create([
            'user_id' => $customer->id, 'customer_name' => $customer->fullname,
            'customer_email' => $customer->email, 'customer_phone' => $customer->phone,
            'check_in' => now()->addDays(5)->toDateString(), 'check_out' => now()->addDays(6)->toDateString(),
            'adult_count' => 1, 'child_count' => 0, 'total_price' => 10000,
            'deposit_amount' => 5000, 'payment_status' => 'pending', 'status' => $status,
        ]);
    }

    private function asCustomer(User $customer): static
    {
        return $this->actingAs($customer)->withSession([
            'customer_user_id' => $customer->id, 'user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
            'user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
        ]);
    }
}
