<?php

namespace Tests\Feature;

use App\Models\Room;
use App\Models\RoomType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_create_renders_selected_specs_and_no_buffet(): void
    {
        $type = RoomType::create([
            'type_name' => 'Phòng Đơn Tiêu Chuẩn',
            'price' => 10000,
            'max_adults' => 2,
            'max_children' => 1,
            'max_guests' => 3,
            'image' => 'images/rooms/1.jpg',
        ]);
        $room1 = Room::create(['room_number' => '101', 'room_type_id' => $type->id, 'floor' => 1, 'status' => 'available']);
        $room2 = Room::create(['room_number' => '102', 'room_type_id' => $type->id, 'floor' => 1, 'status' => 'available']);

        $customer = User::create([
            'username' => 'testcustomer@hotel.local',
            'email' => 'testcustomer@hotel.local',
            'password' => bcrypt('Secret123!'),
            'fullname' => 'Văn Quang',
            'phone' => '0912345678',
            'role' => 'customer',
            'verified' => true,
        ]);

        $checkIn = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(4)->toDateString();

        $response = $this->actingAs($customer)->withSession([
            'auth_user_id' => $customer->id,
            'user_id' => $customer->id,
            'customer_user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
            'user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
        ])->get(route('booking.create', [
            'room_ids' => [$room1->id, $room2->id],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 2,
            'children' => 1,
        ]));

        $response->assertOk();
        $content = $response->getContent();

        // 1. Assert old static buffet checkmarks are GONE
        $this->assertStringNotContainsString('Buffet sáng cao cấp tại Terrace Horizon', $content);
        $this->assertStringNotContainsString('Đưa đón sân bay VIP chuyên cơ/Mercedes', $content);
        $this->assertStringNotContainsString('60 phút trị liệu Signature Spa đôi', $content);

        // 2. Assert selected booking specifications ARE present
        $this->assertStringContainsString('Thời gian:', $content);
        $this->assertStringContainsString('2 đêm', $content);
        $this->assertStringContainsString('Số lượng phòng:', $content);
        $this->assertStringContainsString('2 phòng', $content);
        $this->assertStringContainsString('Phòng đã chọn:', $content);
        $this->assertStringContainsString('Phòng 101, 102', $content);
        $this->assertStringContainsString('Số lượng khách:', $content);
        $this->assertStringContainsString('3 khách', $content);
        $this->assertStringContainsString('2 lớn', $content);
        $this->assertStringContainsString('1 trẻ', $content);

        // 3. Single room test
        $singleResponse = $this->actingAs($customer)->withSession([
            'auth_user_id' => $customer->id,
            'user_id' => $customer->id,
            'customer_user_id' => $customer->id,
            'customer_user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
            'user' => ['id' => $customer->id, 'role' => 'customer', 'verified' => true],
        ])->get(route('booking.create', [
            'room_ids' => [$room1->id],
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'adults' => 1,
            'children' => 0,
        ]));

        $singleResponse->assertOk();
        $singleContent = $singleResponse->getContent();

        $this->assertStringContainsString('1 phòng', $singleContent);
        $this->assertStringContainsString('Phòng 101', $singleContent);
        $this->assertStringContainsString('1 khách', $singleContent);
        $this->assertStringContainsString('1 lớn', $singleContent);

        file_put_contents(
            'C:\\Users\\thinh\\.gemini\\antigravity\\brain\\f7f04c79-eb8e-4629-a3ea-4f9b473252e5\\booking_step1_1room_rendered.html',
            $singleContent
        );

        echo "TEST PASSED AND HTML SAVED SUCCESSFULLY!\n";
    }
}
