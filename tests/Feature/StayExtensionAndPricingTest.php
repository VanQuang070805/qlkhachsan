<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StayExtensionAndPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_extend_a_confirmed_booking_when_the_room_is_free(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('confirmed', 600000);
        $oldCheckOut = $booking->check_out->toDateString();

        $this->asCustomer($customer)->get(route('booking.mine'))
            ->assertOk()
            ->assertSee('Gia hạn lưu trú')
            ->assertSee(route('booking.extend', $booking), false);

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id,
            'days' => 2,
        ])->assertRedirect()->assertSessionHas('success');

        $booking->refresh();
        $this->assertSame(Carbon::parse($oldCheckOut)->addDays(2)->toDateString(), $booking->check_out->toDateString());
        $this->assertSame(2400000.0, (float) $booking->total_price);
    }

    public function test_customer_extension_is_blocked_when_the_room_has_a_following_reservation(): void
    {
        [$customer, $booking, $room] = $this->bookingWithRoom('confirmed', 600000);
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
            'days' => 1,
        ])->assertRedirect()->assertSessionHasErrors('days');

        $this->assertSame($oldCheckOut, $booking->fresh()->check_out->toDateString());
        $this->assertSame(1200000.0, (float) $booking->fresh()->total_price);
    }

    public function test_customer_cannot_extend_another_customers_booking(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('confirmed', 600000);
        $intruder = $this->customer('extension-intruder@example.com');

        $this->asCustomer($intruder)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id,
            'days' => 1,
        ])->assertForbidden();
    }

    public function test_customer_cannot_extend_on_the_checkout_date(): void
    {
        [$customer, $booking] = $this->bookingWithRoom('confirmed', 600000);
        $booking->update([
            'check_in' => now('Asia/Ho_Chi_Minh')->subDay()->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->toDateString(),
        ]);

        $this->asCustomer($customer)->post(route('booking.extend', $booking), [
            'booking_id' => $booking->id,
            'days' => 1,
        ])->assertRedirect()->assertSessionHasErrors('days');
    }

    public function test_no_show_is_cancelled_at_the_end_of_the_checkin_window(): void
    {
        $this->travelTo(Carbon::parse('2026-10-04 16:59:00', 'Asia/Ho_Chi_Minh'));
        [$customer, $booking] = $this->bookingWithRoom('confirmed', 600000);
        $booking->update([
            'check_in' => now('Asia/Ho_Chi_Minh')->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDays(2)->toDateString(),
        ]);

        $this->artisan('bookings:expire-no-shows')->assertExitCode(0);
        $this->assertSame('confirmed', $booking->fresh()->status);

        $this->travelTo(Carbon::parse('2026-10-04 17:00:00', 'Asia/Ho_Chi_Minh'));
        $this->artisan('bookings:expire-no-shows')->assertExitCode(0);

        $booking->refresh();
        $this->assertSame('cancelled', $booking->status);
        $this->assertSame('Không đến nhận phòng trước 17:00.', $booking->cancellation_reason);
        $this->assertNotNull($booking->cancelled_at);
        $this->asCustomer($customer)->get(route('booking.mine'))
            ->assertOk()
            ->assertSee('Đã hủy')
            ->assertDontSee('Gia hạn lưu trú');
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

    public function test_admin_can_update_the_canonical_room_type_price(): void
    {
        $admin = $this->internalUser('price-admin@example.com', 'admin');
        $type = $this->roomType(600000);

        $this->asStaff($admin)->get(route('admin.price-settings.index'))
            ->assertOk()
            ->assertSee('Giá nền theo hạng phòng')
            ->assertSee($type->type_name)
            ->assertSee(route('admin.room-types.price.update', $type), false);

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
