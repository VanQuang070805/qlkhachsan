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
            $table->longText('guest_cccd')->nullable()->after('guest_name');
            $table->longText('guest_phone')->nullable()->after('guest_cccd');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement(<<<'SQL'
                UPDATE face_profiles
                   SET guest_name = (SELECT customer_name FROM bookings WHERE bookings.id = face_profiles.booking_id)
                 WHERE guest_name IS NULL
            SQL);
        } else {
            DB::table('face_profiles')
                ->join('bookings', 'bookings.id', '=', 'face_profiles.booking_id')
                ->whereNull('face_profiles.guest_name')
                ->update([
                    'face_profiles.guest_name' => DB::raw('bookings.customer_name'),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('face_profiles', function (Blueprint $table) {
            $table->dropColumn(['guest_name', 'guest_cccd', 'guest_phone']);
        });
    }
};
