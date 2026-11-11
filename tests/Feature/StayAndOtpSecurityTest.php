<?php

namespace Tests\Feature;

use App\Models\PriceSetting;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StayAndOtpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_quote_and_availability_reject_excessive_stays_but_accept_thirty_nights(): void
    {
        $roomType = RoomType::create([
            'type_name' => 'Phòng kiểm thử',
            'price' => 100000,
            'max_adults' => 2,
            'max_children' => 0,
            'max_guests' => 2,
            'description' => 'Kiểm thử',
        ]);
        $checkIn = now('Asia/Ho_Chi_Minh')->addDays(5)->toDateString();
        $validOut = now('Asia/Ho_Chi_Minh')->addDays(35)->toDateString();
        $invalidOut = now('Asia/Ho_Chi_Minh')->addDays(36)->toDateString();

        $this->postJson('/api/v1/public/quotes', [
            'room_type_id' => $roomType->id, 'check_in' => $checkIn, 'check_out' => $validOut,
        ])->assertOk()->assertJsonPath('data.nights', 30);

        $this->postJson('/api/v1/public/quotes', [
            'room_type_id' => $roomType->id, 'check_in' => $checkIn, 'check_out' => $invalidOut,
        ])->assertStatus(422)->assertJsonPath('error.code', 'VALIDATION_ERROR');

        $this->getJson('/api/v1/public/rooms/availability?'.http_build_query([
            'check_in' => $checkIn, 'check_out' => $invalidOut,
        ]))->assertStatus(422)->assertJsonPath('error.code', 'INVALID_AVAILABILITY_QUERY');

        $this->postJson('/api/v1/public/quotes', [
            'room_type_id' => $roomType->id,
            'check_in' => now('Asia/Ho_Chi_Minh')->addDays(366)->toDateString(),
            'check_out' => now('Asia/Ho_Chi_Minh')->addDays(367)->toDateString(),
        ])->assertStatus(422);
    }

    public function test_price_calculator_rejects_unbounded_ranges_at_shared_sink(): void
    {
        $this->expectException(ValidationException::class);
        PriceSetting::calculateTotalPrice(100000, now()->addDays(1)->toDateString(), '9999-12-31');
    }

    public function test_api_email_verification_rejects_expired_otp_and_consumes_valid_otp(): void
    {
        $user = User::create([
            'username' => 'otp-security@example.com',
            'fullname' => 'OTP Security',
            'email' => 'otp-security@example.com',
            'phone' => '0901234567',
            'password' => Hash::make('SecurePass123!'),
            'role' => 'customer',
            'verified' => false,
            'otp_code' => Hash::make('123456'),
            'otp_expires_at' => now()->subMinute(),
        ]);

        $payload = ['email' => $user->email, 'otp' => '123456'];
        $this->postJson('/api/v1/customer/auth/verify-email', $payload)
            ->assertStatus(422)->assertJsonPath('error.code', 'INVALID_OTP');
        $this->assertFalse($user->fresh()->verified);

        $user->update(['otp_expires_at' => now()->addMinutes(10)]);
        $this->postJson('/api/v1/customer/auth/verify-email', $payload)
            ->assertOk()->assertJsonPath('data.verified', true);
        $this->assertNull($user->fresh()->otp_code);
        $this->assertNull($user->fresh()->otp_expires_at);
        $this->postJson('/api/v1/customer/auth/verify-email', $payload)
            ->assertStatus(422)->assertJsonPath('error.code', 'INVALID_OTP');
    }

    public function test_otp_verification_is_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $this->postJson('/api/v1/customer/auth/verify-email', [
                'email' => 'unknown@example.com', 'otp' => '000000',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/customer/auth/verify-email', [
            'email' => 'unknown@example.com', 'otp' => '000000',
        ])->assertStatus(429);
    }

    public function test_intended_url_stays_relative_to_the_current_portal(): void
    {
        $this->withServerVariables(['HTTP_HOST' => 'attacker.example'])
            ->get('/my-bookings?tab=upcoming')
            ->assertRedirect(route('login'));

        $this->assertSame('/my-bookings?tab=upcoming', session('url.intended'));
    }

    public function test_api_registration_and_resend_issue_distinct_usable_otp_codes(): void
    {
        $codes = [];
        Mail::shouldReceive('raw')->twice()->withArgs(function (string $body) use (&$codes): bool {
            preg_match('/Mã OTP của bạn là: ([0-9]{6})/', $body, $matches);
            $codes[] = $matches[1] ?? null;

            return true;
        });

        $email = 'new-api-customer@example.com';
        $this->postJson('/api/v1/customer/auth/register', [
            'name' => 'New API Customer',
            'email' => strtoupper($email),
            'phone' => '0901234567',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertCreated()->assertJsonPath('data.verified', false);

        $user = User::where('email', $email)->firstOrFail();
        $this->assertTrue(Hash::check($codes[0], $user->otp_code));
        $this->assertNotNull($user->otp_expires_at);

        $this->postJson('/api/v1/customer/auth/resend-otp', ['email' => $email])
            ->assertOk()->assertJsonPath('data.accepted', true);
        $this->assertTrue(Hash::check($codes[1], $user->fresh()->otp_code));
        $this->postJson('/api/v1/customer/auth/verify-email', ['email' => $email, 'otp' => $codes[1]])
            ->assertOk()->assertJsonPath('data.verified', true);
    }

    public function test_api_password_reset_requires_current_otp_changes_password_and_revokes_token(): void
    {
        $code = null;
        Mail::shouldReceive('raw')->once()->withArgs(function (string $body) use (&$code): bool {
            preg_match('/Mã OTP của bạn là: ([0-9]{6})/', $body, $matches);
            $code = $matches[1] ?? null;

            return true;
        });

        $user = User::create([
            'username' => 'reset-api@example.com', 'fullname' => 'Reset API',
            'email' => 'reset-api@example.com', 'phone' => '0901234567',
            'password' => Hash::make('OldPassword123!'), 'role' => 'customer', 'verified' => true,
        ]);
        $user->forceFill(['remember_token' => 'previous-token'])->save();
        DB::table('sessions')->insert([
            'id' => 'previous-customer-session', 'user_id' => $user->id,
            'payload' => 'test', 'last_activity' => time(),
        ]);

        $this->postJson('/api/v1/customer/auth/password/forgot', ['email' => $user->email])
            ->assertOk()->assertJsonPath('data.accepted', true);
        $this->assertTrue(Hash::check($code, $user->fresh()->otp_code));

        $payload = [
            'email' => $user->email, 'otp' => $code,
            'password' => 'NewPassword456!', 'password_confirmation' => 'NewPassword456!',
        ];
        $this->postJson('/api/v1/customer/auth/password/reset', array_replace($payload, ['otp' => 'abcdef']))
            ->assertStatus(422);
        $this->postJson('/api/v1/customer/auth/password/reset', $payload)
            ->assertOk()->assertJsonPath('data.reset', true);

        $user->refresh();
        $this->assertTrue(Hash::check('NewPassword456!', $user->password));
        $this->assertFalse(Hash::check('OldPassword123!', $user->password));
        $this->assertNotSame('previous-token', $user->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'previous-customer-session']);
        $this->assertNull($user->otp_code);
        $this->assertNull($user->otp_expires_at);
        $this->postJson('/api/v1/customer/auth/password/reset', $payload)
            ->assertStatus(422)->assertJsonPath('error.code', 'INVALID_OTP');
    }

    public function test_api_password_reset_rejects_expired_otp_and_unknown_email_is_generic(): void
    {
        Mail::shouldReceive('raw')->never();
        $this->postJson('/api/v1/customer/auth/password/forgot', ['email' => 'missing@example.com'])
            ->assertOk()->assertJsonPath('data.accepted', true);

        $user = User::create([
            'username' => 'expired-api@example.com', 'fullname' => 'Expired API',
            'email' => 'expired-api@example.com', 'phone' => '0901234567',
            'password' => Hash::make('OldPassword123!'), 'role' => 'customer', 'verified' => true,
            'otp_code' => Hash::make('654321'), 'otp_expires_at' => now()->subMinute(),
        ]);
        $this->postJson('/api/v1/customer/auth/password/reset', [
            'email' => $user->email, 'otp' => '654321',
            'password' => 'NewPassword456!', 'password_confirmation' => 'NewPassword456!',
        ])->assertStatus(422)->assertJsonPath('error.code', 'INVALID_OTP');
        $this->assertTrue(Hash::check('OldPassword123!', $user->fresh()->password));
    }

    public function test_api_otp_send_is_rate_limited_and_registration_reports_mail_failure(): void
    {
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->postJson('/api/v1/customer/auth/register', [
            'name' => 'Mail Failure', 'email' => 'mail-failure@example.com',
            'phone' => '0901234567', 'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertStatus(503)->assertJsonPath('error.code', 'EMAIL_UNAVAILABLE');
        $this->assertDatabaseHas('users', ['email' => 'mail-failure@example.com', 'verified' => false]);

        $this->postJson('/api/v1/customer/auth/resend-otp', ['email' => 'missing@example.com'])
            ->assertOk()->assertJsonPath('data.accepted', true);
        $this->postJson('/api/v1/customer/auth/resend-otp', ['email' => 'missing@example.com'])
            ->assertStatus(429);
    }

    public function test_failed_reset_email_does_not_invalidate_a_previous_otp(): void
    {
        $user = User::create([
            'username' => 'mail-reset-failure@example.com', 'fullname' => 'Mail Reset Failure',
            'email' => 'mail-reset-failure@example.com', 'phone' => '0901234567',
            'password' => Hash::make('OldPassword123!'), 'role' => 'customer', 'verified' => true,
            'otp_code' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(5),
        ]);
        $previousHash = $user->otp_code;
        Mail::shouldReceive('raw')->once()->andThrow(new \RuntimeException('SMTP unavailable'));

        $this->postJson('/api/v1/customer/auth/password/forgot', ['email' => $user->email])
            ->assertOk()->assertJsonPath('data.accepted', true);

        $this->assertSame($previousHash, $user->fresh()->otp_code);
    }
}
