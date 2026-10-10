<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INTERNAL_PERMISSIONS = [
        'bp_view_schedule',
        'bp_create_booking',
        'bp_checkin_checkout',
        'bp_extend_stay',
        'bp_clean_status',
        'tn_collect_payment',
        'tn_refund',
    ];

    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 80)->unique();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('permission_key', 100);
            $table->timestamps();
            $table->primary(['role_id', 'permission_key']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('role_id')->nullable()->after('role')
                ->constrained('roles')->restrictOnDelete()->cascadeOnUpdate();
        });

        $now = now();
        DB::table('roles')->insert([
            ['slug' => 'customer', 'name' => 'Khách hàng', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'receptionist', 'name' => 'Lễ tân', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'admin', 'name' => 'Quản trị viên', 'is_system' => true, 'created_at' => $now, 'updated_at' => $now],
            // Preserve the existing Kế toán permission profile as a database role.
            ['slug' => 'ke-toan', 'name' => 'Kế toán', 'is_system' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);

        $roleIds = DB::table('roles')->pluck('id', 'slug');
        $rolePermissionRows = [];
        foreach (self::INTERNAL_PERMISSIONS as $permissionKey) {
            $rolePermissionRows[] = [
                'role_id' => $roleIds['receptionist'],
                'permission_key' => $permissionKey,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        foreach (['tn_collect_payment', 'tn_refund'] as $permissionKey) {
            $rolePermissionRows[] = [
                'role_id' => $roleIds['ke-toan'],
                'permission_key' => $permissionKey,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('role_permissions')->insert($rolePermissionRows);

        DB::table('users')->orderBy('id')->get(['id', 'role'])->each(function (object $user) use ($roleIds): void {
            DB::table('users')->where('id', $user->id)->update([
                'role_id' => $roleIds[$user->role] ?? $roleIds['receptionist'],
            ]);
        });

        if (Schema::hasTable('user_permissions')) {
            $accountantUsers = DB::table('user_permissions')
                ->where('permission_key', '_internal_role_profile:ke-toan')
                ->pluck('user_id');
            if ($accountantUsers->isNotEmpty()) {
                DB::table('users')->whereIn('id', $accountantUsers)->update(['role_id' => $roleIds['ke-toan']]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('role_id');
        });
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
    }
};
