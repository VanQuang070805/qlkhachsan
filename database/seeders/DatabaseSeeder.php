<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local', 'testing') || !env('LOCAL_DEMO_PASSWORD')) {
            throw new \RuntimeException('Sample data requires local/testing and LOCAL_DEMO_PASSWORD in .env.');
        }
        if (DB::table('users')->exists() || DB::table('rooms')->exists()) {
            throw new \RuntimeException('Sample data requires an empty database. Existing data was not changed.');
        }
        DB::transaction(function () {
        // ======================================
        // USERS
        // ======================================
        DB::table('users')->insert([
            [
                'username'   => 'admin',
                'password'   => Hash::make(env('LOCAL_DEMO_PASSWORD')),
                'fullname'   => 'Quản trị hệ thống',
                'email'      => 'admin@hotel.com',
                'phone'      => '0900000001',
                'role'       => 'admin',
                'verified'   => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username'   => 'reception',
                'password'   => Hash::make(env('LOCAL_DEMO_PASSWORD')),
                'fullname'   => 'Lễ tân khách sạn',
                'email'      => 'reception@hotel.com',
                'phone'      => '0900000002',
                'role'       => 'receptionist',
                'verified'   => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'username'   => 'customer',
                'password'   => Hash::make(env('LOCAL_DEMO_PASSWORD')),
                'fullname'   => 'Nguyễn Văn A',
                'email'      => 'customer@gmail.com',
                'phone'      => '0900000003',
                'role'       => 'customer',
                'verified'   => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // ======================================
        // ROOM TYPES
        // ======================================
        DB::table('room_types')->insert([
            ['type_name' => 'Phòng Đơn Tiêu Chuẩn', 'price' => 400000,  'max_adults' => 1, 'max_children' => 0, 'max_guests' => 1, 'description' => 'Phòng dành cho 1 khách', 'created_at' => now(), 'updated_at' => now()],
            ['type_name' => 'Phòng Đôi Tiêu Chuẩn',  'price' => 650000,  'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3, 'description' => 'Phòng dành cho 2 người lớn và 1 trẻ em', 'created_at' => now(), 'updated_at' => now()],
            ['type_name' => 'Phòng Triple',            'price' => 900000,  'max_adults' => 3, 'max_children' => 1, 'max_guests' => 4, 'description' => 'Phòng dành cho nhóm khách', 'created_at' => now(), 'updated_at' => now()],
            ['type_name' => 'Phòng Gia Đình',          'price' => 1200000, 'max_adults' => 4, 'max_children' => 2, 'max_guests' => 6, 'description' => 'Phòng dành cho gia đình', 'created_at' => now(), 'updated_at' => now()],
            ['type_name' => 'Phòng VIP',               'price' => 2000000, 'max_adults' => 2, 'max_children' => 2, 'max_guests' => 4, 'description' => 'Phòng cao cấp với nhiều tiện nghi', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ======================================
        // AMENITIES
        // ======================================
        DB::table('amenities')->insert([
            ['amenity_name' => 'WiFi miễn phí',       'icon' => 'fa-wifi',           'created_at' => now(), 'updated_at' => now()],
            ['amenity_name' => 'TV màn hình phẳng',   'icon' => 'fa-tv',             'created_at' => now(), 'updated_at' => now()],
            ['amenity_name' => 'Điều hòa',             'icon' => 'fa-snowflake',      'created_at' => now(), 'updated_at' => now()],
            ['amenity_name' => 'Máy sấy tóc',          'icon' => 'fa-wind',           'created_at' => now(), 'updated_at' => now()],
            ['amenity_name' => 'Dịch vụ phòng',        'icon' => 'fa-concierge-bell', 'created_at' => now(), 'updated_at' => now()],
            ['amenity_name' => 'Tủ lạnh',              'icon' => 'fa-cube',           'created_at' => now(), 'updated_at' => now()],
            ['amenity_name' => 'Bồn tắm',              'icon' => 'fa-bath',           'created_at' => now(), 'updated_at' => now()],
            ['amenity_name' => 'Máy chiếu',            'icon' => 'fa-film',           'created_at' => now(), 'updated_at' => now()],
            ['amenity_name' => 'Bữa sáng miễn phí',   'icon' => 'fa-coffee',         'created_at' => now(), 'updated_at' => now()],
        ]);

        // ======================================
        // ROOM TYPE AMENITIES
        // ======================================
        DB::table('room_type_amenities')->insert([
            // Đơn: WiFi, TV, Điều hòa, Máy sấy tóc
            ['room_type_id' => 1, 'amenity_id' => 1],
            ['room_type_id' => 1, 'amenity_id' => 2],
            ['room_type_id' => 1, 'amenity_id' => 3],
            ['room_type_id' => 1, 'amenity_id' => 4],
            // Đôi: + Dịch vụ phòng, Tủ lạnh
            ['room_type_id' => 2, 'amenity_id' => 1],
            ['room_type_id' => 2, 'amenity_id' => 2],
            ['room_type_id' => 2, 'amenity_id' => 3],
            ['room_type_id' => 2, 'amenity_id' => 4],
            ['room_type_id' => 2, 'amenity_id' => 5],
            ['room_type_id' => 2, 'amenity_id' => 6],
            // Triple
            ['room_type_id' => 3, 'amenity_id' => 1],
            ['room_type_id' => 3, 'amenity_id' => 2],
            ['room_type_id' => 3, 'amenity_id' => 3],
            ['room_type_id' => 3, 'amenity_id' => 4],
            ['room_type_id' => 3, 'amenity_id' => 5],
            ['room_type_id' => 3, 'amenity_id' => 6],
            // Gia Đình: + Bồn tắm
            ['room_type_id' => 4, 'amenity_id' => 1],
            ['room_type_id' => 4, 'amenity_id' => 2],
            ['room_type_id' => 4, 'amenity_id' => 3],
            ['room_type_id' => 4, 'amenity_id' => 4],
            ['room_type_id' => 4, 'amenity_id' => 5],
            ['room_type_id' => 4, 'amenity_id' => 6],
            ['room_type_id' => 4, 'amenity_id' => 7],
            // VIP: tất cả
            ['room_type_id' => 5, 'amenity_id' => 1],
            ['room_type_id' => 5, 'amenity_id' => 2],
            ['room_type_id' => 5, 'amenity_id' => 3],
            ['room_type_id' => 5, 'amenity_id' => 4],
            ['room_type_id' => 5, 'amenity_id' => 5],
            ['room_type_id' => 5, 'amenity_id' => 6],
            ['room_type_id' => 5, 'amenity_id' => 7],
            ['room_type_id' => 5, 'amenity_id' => 8],
            ['room_type_id' => 5, 'amenity_id' => 9],
        ]);

        // ======================================
        // ROOMS (25 phòng, 5 tầng)
        // ======================================
        DB::table('rooms')->insert([
            // Tầng 1
            ['room_number' => '101', 'room_type_id' => 1, 'floor' => 1, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '102', 'room_type_id' => 1, 'floor' => 1, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '103', 'room_type_id' => 1, 'floor' => 1, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '104', 'room_type_id' => 5, 'floor' => 1, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '105', 'room_type_id' => 5, 'floor' => 1, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            // Tầng 2
            ['room_number' => '201', 'room_type_id' => 1, 'floor' => 2, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '202', 'room_type_id' => 1, 'floor' => 2, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '203', 'room_type_id' => 4, 'floor' => 2, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '204', 'room_type_id' => 4, 'floor' => 2, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '205', 'room_type_id' => 5, 'floor' => 2, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            // Tầng 3
            ['room_number' => '301', 'room_type_id' => 1, 'floor' => 3, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '302', 'room_type_id' => 1, 'floor' => 3, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '303', 'room_type_id' => 4, 'floor' => 3, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '304', 'room_type_id' => 5, 'floor' => 3, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '305', 'room_type_id' => 3, 'floor' => 3, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            // Tầng 4
            ['room_number' => '401', 'room_type_id' => 2, 'floor' => 4, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '402', 'room_type_id' => 2, 'floor' => 4, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '403', 'room_type_id' => 4, 'floor' => 4, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '404', 'room_type_id' => 3, 'floor' => 4, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '405', 'room_type_id' => 3, 'floor' => 4, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            // Tầng 5
            ['room_number' => '501', 'room_type_id' => 2, 'floor' => 5, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '502', 'room_type_id' => 2, 'floor' => 5, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '503', 'room_type_id' => 4, 'floor' => 5, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '504', 'room_type_id' => 3, 'floor' => 5, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['room_number' => '505', 'room_type_id' => 3, 'floor' => 5, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ======================================
        // HOLIDAYS (ngày lễ Việt Nam)
        // ======================================
        DB::table('holidays')->insert([
            ['name' => 'Tết Dương lịch',        'date' => '2026-01-01', 'recurring' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tết Nguyên Đán (30)',    'date' => '2026-02-16', 'recurring' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tết Nguyên Đán (Mùng 1)','date' => '2026-02-17', 'recurring' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tết Nguyên Đán (Mùng 2)','date' => '2026-02-18', 'recurring' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tết Nguyên Đán (Mùng 3)','date' => '2026-02-19', 'recurring' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tết Nguyên Đán (Mùng 4)','date' => '2026-02-20', 'recurring' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tết Nguyên Đán (Mùng 5)','date' => '2026-02-21', 'recurring' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Giỗ Tổ Hùng Vương',      'date' => '2026-04-06', 'recurring' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ngày Giải phóng 30/4',   'date' => '2026-04-30', 'recurring' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ngày Quốc tế Lao động',  'date' => '2026-05-01', 'recurring' => true,  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ngày Quốc khánh 2/9',    'date' => '2026-09-02', 'recurring' => true,  'created_at' => now(), 'updated_at' => now()],
        ]);

        // ======================================
        // PRICE POLICIES
        // ======================================
        DB::table('price_settings')->insert([
            ['name' => 'Dịp lễ Tết', 'start_date' => '2026-02-16', 'end_date' => '2026-02-21', 'adjustment_type' => 'percent', 'adjustment_value' => 50, 'status' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // ======================================
        // BOOKINGS (data mẫu)
        // ======================================
        DB::table('bookings')->insert([
            [
                'user_id'          => 3,
                'customer_name'    => 'Nguyễn Văn A',
                'customer_email'   => 'customer@gmail.com',
                'customer_phone'   => '0900000003',
                'check_in'         => '2026-06-20',
                'check_out'        => '2026-06-22',
                'adult_count'      => 2,
                'child_count'      => 1,
                'total_price'      => 1300000,
                'payment_method'   => 'vietqr',
                'payment_status'   => 'paid',
                'status'           => 'confirmed',
                'refund_status'    => 'none',
                'refund_amount'    => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
            [
                'user_id'          => 3,
                'customer_name'    => 'Nguyễn Văn A',
                'customer_email'   => 'customer@gmail.com',
                'customer_phone'   => '0900000003',
                'check_in'         => '2026-07-01',
                'check_out'        => '2026-07-03',
                'adult_count'      => 1,
                'child_count'      => 0,
                'total_price'      => 800000,
                'payment_method'   => 'vietqr',
                'payment_status'   => 'paid',
                'status'           => 'completed',
                'refund_status'    => 'none',
                'refund_amount'    => 0,
                'created_at'       => now(),
                'updated_at'       => now(),
            ],
        ]);

        DB::table('booking_rooms')->insert([
            ['booking_id' => 1, 'room_id' => 16], // phòng 401
            ['booking_id' => 2, 'room_id' => 1],  // phòng 101
        ]);

        // ======================================
        // REVIEWS
        // ======================================
        DB::table('reviews')->insert([
            ['user_id' => 3, 'room_type_id' => 2, 'rating' => 5, 'comment' => 'Phòng sạch sẽ, nhân viên thân thiện', 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 3, 'room_type_id' => 1, 'rating' => 4, 'comment' => 'Giá hợp lý, đầy đủ tiện nghi',        'created_at' => now(), 'updated_at' => now()],
        ]);
        });
    }
}
