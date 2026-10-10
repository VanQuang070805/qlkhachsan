<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo seeding is restricted to local and testing environments.');
        }

        DB::transaction(fn () => $this->seedDemoData());
    }

    private function seedDemoData(): void
    {
        $this->call(InternalAccountsSeeder::class);

        // ======================================
        // ROOM TYPES
        // ======================================
        DB::table('room_types')->insert([
            ['type_name' => 'Phòng Đơn Tiêu Chuẩn', 'price' => 10000,  'max_adults' => 1, 'max_children' => 0, 'max_guests' => 1, 'description' => 'Phòng dành cho 1 khách', 'created_at' => now(), 'updated_at' => now()],
            ['type_name' => 'Phòng Đôi Tiêu Chuẩn',  'price' => 20000,  'max_adults' => 2, 'max_children' => 1, 'max_guests' => 3, 'description' => 'Phòng dành cho 2 người lớn và 1 trẻ em', 'created_at' => now(), 'updated_at' => now()],
            ['type_name' => 'Phòng Triple',            'price' => 50000,  'max_adults' => 3, 'max_children' => 1, 'max_guests' => 4, 'description' => 'Phòng dành cho nhóm khách', 'created_at' => now(), 'updated_at' => now()],
            ['type_name' => 'Phòng Gia Đình',          'price' => 100000, 'max_adults' => 4, 'max_children' => 2, 'max_guests' => 6, 'description' => 'Phòng dành cho gia đình', 'created_at' => now(), 'updated_at' => now()],
            ['type_name' => 'Phòng VIP',               'price' => 100000, 'max_adults' => 2, 'max_children' => 2, 'max_guests' => 4, 'description' => 'Phòng cao cấp với nhiều tiện nghi', 'created_at' => now(), 'updated_at' => now()],
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
        // 25 phòng đầu tiên (tầng 1–5); RoomCapacitySeeder thêm tầng 6–10.
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
        $this->call(RoomCapacitySeeder::class);

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
        // PRICE SETTINGS
        // ======================================
        DB::table('price_settings')->insert([
            // price_settings supports date ranges, not recurring weekdays; avoid applying a weekend rule to every day of 2026.
            ['name' => 'Dịp lễ Tết', 'start_date' => '2026-02-16', 'end_date' => '2026-02-21', 'adjustment_type' => 'percent', 'adjustment_value' => 50, 'status' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

    }
}
