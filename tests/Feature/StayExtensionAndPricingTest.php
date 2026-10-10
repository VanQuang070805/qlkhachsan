<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StayExtensionAndPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_extend_an_in_house_booking_by_days_when_the_room_is_free(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('checked_in', 600000);
        $oldCheckOut = $booking->check_out->toDateString();

        $this->asCustomer($customer)->get(route('booking.mine'))
            ->assertOk()
            ->assertSee('Gia hạn phòng')
            ->assertSee('id="stayExtensionModal"', false)
            ->assertSee(route('booking.extend', $booking), false);

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id,
            'mode' => 'days',
            'amount' => 2,
        ])->assertRedirect()->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(Carbon::parse($oldCheckOut)->addDays(2)->toDateString(), $booking->check_out->toDateString());
        $this->assertSame(2400000.0, (float) $booking->total_price);
    }

    public function test_customer_extension_is_blocked_when_the_room_has_a_following_reservation(): void
    {
        [$customer, $booking, $room] = $this->bookingWithRoom('checked_in', 600000);
        $other = $this->booking(
            $this->customer('following-guest@example.com'),
            'confirmed',
            $booking->check_out->toDateString(),
            $booking->check_out->copy()->addDays(2)->toDateString(),
            1200000
        );
        $other->rooms()->attach($room->id);
        $oldCheckOut = $booking->check_out->toDateString();

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id,
            'mode' => 'days',
            'amount' => 1,
        ])->assertRedirect()->assertSessionHasErrors('amount');

        $this->assertSame($oldCheckOut, $booking->fresh()->check_out->toDateString());
        $this->assertSame(1200000.0, (float) $booking->fresh()->total_price);
    }

    public function test_customer_cannot_extend_another_customers_booking(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('checked_in', 600000);
        $intruder = $this->customer('extension-intruder@example.com');

        $this->asCustomer($intruder)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id,
            'mode' => 'days',
            'amount' => 1,
        ])->assertForbidden();
    }

    public function test_customer_cannot_extend_before_checkin(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('confirmed', 600000);

        $this->asCustomer($customer)->get(route('booking.mine'))
            ->assertOk()
            ->assertDontSee(route('booking.extend', $booking), false);

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id,
            'mode' => 'hours',
            'amount' => 1,
        ])->assertRedirect()->assertSessionHasErrors('amount');
    }

    public function test_customer_can_extend_an_in_house_booking_by_hours_at_ten_percent_per_room_per_hour(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('checked_in', 600000);
        $oldCheckOut = $booking->check_out->toDateString();

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id,
            'mode' => 'hours',
            'amount' => 2,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame($oldCheckOut, $booking->fresh()->check_out->toDateString());
        $this->assertSame(1320000.0, (float) $booking->fresh()->total_price);
        $this->assertSame('14:00', $booking->fresh()->scheduledCheckoutAt()->format('H:i'));
        $this->assertSame(120000.0, $booking->fresh()->currentHourlyExtensionCharge());
        $this->assertContains($booking->rooms->first()->id, Booking::reservedRoomIds(
            $oldCheckOut, Carbon::parse($oldCheckOut)->addDay()->toDateString()
        )->all());
    }

    public function test_hourly_extensions_convert_to_one_night_after_18h_without_double_charging(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('checked_in', 600000);
        $oldCheckOut = $booking->check_out->toDateString();

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id, 'mode' => 'hours', 'amount' => 4,
        ])->assertSessionHas('success');
        $this->assertSame(1440000.0, (float) $booking->fresh()->total_price);

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id, 'mode' => 'hours', 'amount' => 3,
        ])->assertSessionHas('success');
        $booking->refresh();
        $this->assertSame(Carbon::parse($oldCheckOut)->addDay()->toDateString(), $booking->check_out->toDateString());
        $this->assertSame(1800000.0, (float) $booking->total_price);
        $this->assertSame('12:00', $booking->scheduledCheckoutAt()->format('H:i'));
        $this->assertSame(0.0, $booking->currentHourlyExtensionCharge());
    }

    public function test_extension_updates_booking_price_without_adding_booking_columns(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('checked_in', 600000);

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id, 'mode' => 'hours', 'amount' => 2,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertFalse(Schema::hasColumn('bookings', 'checkout_due_at'));
        $this->assertFalse(Schema::hasColumn('bookings', 'hourly_extension_charge'));
        $this->assertSame(1320000.0, (float) $booking->fresh()->total_price);
        $this->assertDatabaseHas('payment_logs', [
            'booking_id' => $booking->id,
            'purpose' => 'stay_extension',
            'status' => 'pending',
        ]);
    }

    public function test_daily_extension_is_blocked_if_room_is_booked_on_the_new_checkout_day(): void
    {
        [$customer, $booking, $room] = $this->bookingWithRoom('checked_in', 600000);
        $newCheckout = $booking->check_out->copy()->addDay();
        $following = $this->booking(
            $this->customer('next-day-guest@example.com'),
            'confirmed',
            $newCheckout->toDateString(),
            $newCheckout->copy()->addDay()->toDateString(),
            600000
        );
        $following->rooms()->attach($room->id);

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id, 'mode' => 'days', 'amount' => 1,
        ])->assertRedirect()->assertSessionHasErrors('amount');

        $this->assertSame(1200000.0, (float) $booking->fresh()->total_price);
    }

    public function test_no_show_is_cancelled_at_the_end_of_the_checkin_window(): void
    {
        $this->travelTo(Carbon::parse('2026-10-04 15:59:00', 'Asia/Ho_Chi_Minh'));
        [$customer, $booking] = $this->bookingWithRoom('confirmed', 600000);
        $booking->update([
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString(),
        ]);

        $this->artisan('bookings:expire-no-shows')->assertExitCode(0);
        $this->assertSame('confirmed', $booking->fresh()->status);

        $this->travelTo(Carbon::parse('2026-10-04 16:00:00', 'Asia/Ho_Chi_Minh'));
        $this->artisan('bookings:expire-no-shows')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('Không đến nhận phòng trước 16:00.', $booking->cancellation_reason);
        $this->assertNotNull($booking->cancelled_at);
        $this->asCustomer($customer)->get(route('booking.mine'))
            ->assertOk()
            ->assertSee('Đã hủy')
            ->assertDontSee('data-post-url="'.route('booking.extend', $booking).'"', false);
        $this->travelBack();
    }

    public function test_no_show_expiry_does_not_cancel_a_checked_in_booking(): void
    {
        [, $booking] = $this->bookingWithRoom('checked_in', 600000);
        $booking->update([
            'check_in' => now('Asia/Ho_Chi_Minh')->subDay()->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            'actual_check_in' => now('Asia/Ho_Chi_Minh')->subDay()->setTime(14, 0),
        ]);

        $this->artisan('bookings:expire-no-shows')->assertExitCode(0);

        $this->assertSame('checked_in', $booking->fresh()->status);
    }

    public function test_checkin_at_16h_cancels_the_unchecked_booking_even_before_scheduler_runs(): void
    {
        $this->travelTo(Carbon::parse('2026-10-04 16:00:00', 'Asia/Ho_Chi_Minh'));
        [, $booking] = $this->bookingWithRoom('confirmed', 600000);
        $booking->update(['check_in' => '2026-10-04', 'check_out' => '2026-10-05']);

        try {
            app(\App\Services\BookingTransitionService::class)->checkIn($booking->id);
            $this->fail('Late check-in must be refused.');
        } catch (\DomainException) {
            $this->assertSame('cancelled', $booking->fresh()->status);
        }
        $this->travelBack();
    }

    public function test_early_checkin_is_refused_without_cancelling_the_reservation(): void
    {
        $this->travelTo(Carbon::parse('2026-10-04 11:59:00', 'Asia/Ho_Chi_Minh'));
        [, $booking] = $this->bookingWithRoom('confirmed', 600000);
        $booking->update(['check_in' => '2026-10-04', 'check_out' => '2026-10-05']);

        try {
            app(\App\Services\BookingTransitionService::class)->checkIn($booking->id);
            $this->fail('Early check-in must be refused.');
        } catch (\DomainException) {
            $this->assertSame('confirmed', $booking->fresh()->status);
        }
        $this->travelBack();
    }

    public function test_admin_can_update_the_canonical_room_type_price(): void
    {
        $admin = $this->internalUser('price-admin@example.com', 'admin');
        $type = $this->roomType(600000);

        $this->asStaff($admin)->get(route('admin.price-settings.index'))
            ->assertOk()
            ->assertSee('Lịch điều chỉnh giá')
            ->assertSee('Thêm chính sách')
            ->assertDontSee('Giá nền theo hạng phòng')
            ->assertDontSee(route('admin.room-types.price.update', $type), false);

        $this->asStaff($admin)->patch(route('admin.room-types.price.update', $type), [
            'room_type_id' => $type->id,
            'price' => 725000,
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertSame(725000.0, (float) $type->fresh()->price);
    }

    public function test_receptionist_cannot_update_the_canonical_room_type_price(): void
    {
        $receptionist = $this->internalUser('price-staff@example.com', 'receptionist');
        $type = $this->roomType(600000);

        $this->asStaff($receptionist)->patch(route('admin.room-types.price.update', $type), [
            'room_type_id' => $type->id,
            'price' => 725000,
        ])->assertRedirect(route('staff.bookings'));

        $this->assertSame(600000.0, (float) $type->fresh()->price);
    }

    private function bookingWithRoom(string $status, float $nightlyRate): array
    {
        $customer = $this->customer(uniqid('extension-', true).'@example.com');
        $type = $this->roomType($nightlyRate);
        $room = Room::create([
            'room_number' => 'EX'.uniqid(),
            'room_type_id' => $type->id,
            'floor' => 2,
            'status' => $status === 'checked_in' ? Room::STATUS_OCCUPIED : Room::STATUS_AVAILABLE,
        ]);
        $booking = $this->booking(
            $customer,
            $status,
            now('Asia/Ho_Chi_Minh')->addDay()->toDateString(),
            now('Asia/Ho_Chi_Minh')->addDays(3)->toDateString(),
            $nightlyRate * 2
        );
        $booking->rooms()->attach($room->id);

        return [$customer, $booking, $room];
    }

    private function booking(User $customer, string $status, string $checkIn, string $checkOut, float $total): Booking
    {
        return Booking::create([
            'user_id' => $customer->id,
            'customer_name' => $customer->fullname,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adult_count' => 1,
            'child_count' => 0,
            'total_price' => $total,
            'payment_status' => 'paid',
            'status' => $status,
        ]);
    }

    private function roomType(float $price): RoomType
    {
        return RoomType::create([
            'type_name' => 'Extension '.uniqid(),
            'price' => $price,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
        ]);
    }

    private function customer(string $email): User
    {
        return User::create([
            'username' => $email,
            'fullname' => 'Extension Guest',
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
            'fullname' => 'Pricing Operator',
            'email' => $email,
            'phone' => '0912345678',
            'password' => Hash::make('Password123!'),
            'role' => $role,
            'verified' => true,
        ]);
    }

    private function asCustomer(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'customer_user_id' => $user->id,
            'customer_user' => ['id' => $user->id, 'role' => 'customer', 'verified' => true],
        ]);
    }

    private function asStaff(User $user): static
    {
        return $this->actingAs($user)->withSession([
            'staff_user_id' => $user->id,
            'staff_user' => ['id' => $user->id, 'role' => $user->role, 'verified' => true],
        ]);
    }
}
