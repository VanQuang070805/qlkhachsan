<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('payment_logs', function (Blueprint $table) {
                $table->enum('gateway', ['vietqr', 'momo', 'zalopay', 'vnpay', 'cash'])->change();
                if (!Schema::hasColumn('payment_logs', 'purpose')) {
                    $table->string('purpose', 32)->default('deposit')->after('amount');
                }
            });
        } else {
            DB::statement("ALTER TABLE payment_logs MODIFY COLUMN gateway ENUM('vietqr','momo','zalopay','vnpay','cash') NOT NULL");
            if (!Schema::hasColumn('payment_logs', 'purpose')) {
                Schema::table('payment_logs', fn (Blueprint $table) => $table->string('purpose', 32)->default('deposit')->after('amount'));
            }
        }

        Schema::table('payment_logs', function (Blueprint $table) {
            $table->unique('reference_code', 'payment_logs_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payment_logs', function (Blueprint $table) {
            $table->dropUnique('payment_logs_reference_unique');
            $table->dropColumn('purpose');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE payment_logs MODIFY COLUMN gateway ENUM('vietqr','momo','zalopay','vnpay') NOT NULL");
        }
    }
};
