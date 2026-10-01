<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') return;
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','checked_in','soon_to_checkout','completed','cancelled') DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') return;
        DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','checked_in','completed','cancelled') DEFAULT 'pending'");
    }
};
