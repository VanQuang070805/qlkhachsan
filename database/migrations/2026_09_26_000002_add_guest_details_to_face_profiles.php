<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('face_profiles', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_cccd', 20)->nullable()->after('guest_name');
            $table->string('guest_phone', 30)->nullable()->after('guest_cccd');
            $table->index('guest_cccd', 'face_profiles_guest_cccd_index');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                UPDATE face_profiles
                   SET guest_name = (SELECT customer_name FROM bookings WHERE bookings.id = face_profiles.booking_id),
                       guest_phone = (SELECT customer_phone FROM bookings WHERE bookings.id = face_profiles.booking_id)
                 WHERE guest_name IS NULL
            SQL);
        } else {
            DB::table('face_profiles')
                ->join('bookings', 'bookings.id', '=', 'face_profiles.booking_id')
                ->whereNull('face_profiles.guest_name')
                ->update([
                    'face_profiles.guest_name' => DB::raw('bookings.customer_name'),
                    'face_profiles.guest_phone' => DB::raw('bookings.customer_phone'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('face_profiles', function (Blueprint $table) {
            $table->dropIndex('face_profiles_guest_cccd_index');
            $table->dropColumn(['guest_name', 'guest_cccd', 'guest_phone']);
        });
    }
};
