<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('bookings', fn (Blueprint $table) => $table->enum('payment_method', ['vietqr', 'momo', 'zalopay', 'vnpay', 'cash'])->nullable()->change());
            return;
        }
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_method ENUM('vietqr','momo','zalopay','vnpay','cash') NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;
        DB::statement("ALTER TABLE bookings MODIFY COLUMN payment_method ENUM('vietqr','momo','zalopay','vnpay') NULL");
    }
};
