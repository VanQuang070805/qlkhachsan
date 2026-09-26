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
            $table->index('booking_id', 'face_profiles_booking_id_index');
        });

        Schema::table('face_profiles', function (Blueprint $table) {
            $table->dropUnique(['booking_id']);
            $table->foreignId('room_id')->nullable()->after('booking_id')
                ->constrained('rooms')->cascadeOnUpdate()->cascadeOnDelete();
            $table->index(['room_id', 'active'], 'face_profiles_room_active_index');
        });

        DB::table('face_profiles')->orderBy('id')->get(['id', 'booking_id'])->each(function ($profile) {
            $roomId = DB::table('booking_rooms')
                ->where('booking_id', $profile->booking_id)
                ->orderBy('room_id')
                ->value('room_id');

            if ($roomId) {
                DB::table('face_profiles')->where('id', $profile->id)->update(['room_id' => $roomId]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('face_profiles', function (Blueprint $table) {
            $table->dropIndex('face_profiles_room_active_index');
            $table->dropConstrainedForeignId('room_id');
        });

        Schema::table('face_profiles', function (Blueprint $table) {
            $table->unique('booking_id');
        });

        Schema::table('face_profiles', function (Blueprint $table) {
            $table->dropIndex('face_profiles_booking_id_index');
        });
    }
};
