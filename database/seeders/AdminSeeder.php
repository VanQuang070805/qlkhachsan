<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'username'   => 'admin',
                'password'   => Hash::make('123456'),
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
                'password'   => Hash::make('123456'),
                'fullname'   => 'Lễ Tân',
                'email'      => 'reception@hotel.com',
                'phone'      => '0900000002',
                'role'       => 'receptionist',
                'verified'   => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
