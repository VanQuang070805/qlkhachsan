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
        $adminPassword = env('LOCAL_ADMIN_PASSWORD');
        $staffPassword = env('LOCAL_STAFF_PASSWORD');
        $roleIds = \Illuminate\Support\Facades\Schema::hasTable('roles')
            ? DB::table('roles')->pluck('id', 'slug')
            : collect();
        $hasDeletedAt = \Illuminate\Support\Facades\Schema::hasColumn('users', 'deleted_at');

        $accounts = [
            [
                'username' => 'admin',
                'password' => Hash::make(filled($adminPassword) ? (string) $adminPassword : '123456'),
                'fullname' => 'Quản trị hệ thống',
                'email' => 'admin@hotel.local',
                'role' => 'admin',
                'role_id' => $roleIds['admin'] ?? null,
            ],
            [
                'username' => 'staff',
                'password' => Hash::make(filled($staffPassword) ? (string) $staffPassword : '123456'),
                'fullname' => 'Nhân viên lễ tân',
                'email' => 'staff@hotel.local',
                'role' => 'receptionist',
                'role_id' => $roleIds['receptionist'] ?? null,
            ],
        ];

        foreach ($accounts as $account) {
            $payload = [
                ...$account,
                'verified' => true,
                'updated_at' => $now,
                'created_at' => $now,
            ];
            if ($hasDeletedAt) {
                $payload['deleted_at'] = null;
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('users', 'role_id')) {
                unset($payload['role_id']);
            }
            DB::table('users')->updateOrInsert(
                ['username' => $account['username']],
                $payload,
            );
        }
    }
}
