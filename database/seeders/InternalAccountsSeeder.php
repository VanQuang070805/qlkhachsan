<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InternalAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Internal demo accounts are restricted to local and testing environments.');
        }

        $now = now();
        $accounts = [
            [
                'username' => 'admin',
                'password' => Hash::make(env('LOCAL_ADMIN_PASSWORD', '123456')),
                'fullname' => 'Quản trị hệ thống',
                'email' => 'admin@hotel.local',
                'role' => 'admin',
            ],
            [
                'username' => 'staff',
                'password' => Hash::make(env('LOCAL_STAFF_PASSWORD', '123456')),
                'fullname' => 'Nhân viên lễ tân',
                'email' => 'staff@hotel.local',
                'role' => 'receptionist',
            ],
        ];

        foreach ($accounts as $account) {
            DB::table('users')->updateOrInsert(
                ['username' => $account['username']],
                [
                    ...$account,
                    'verified' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
        }
    }
}
