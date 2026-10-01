<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', fn (Blueprint $table) => $table->unique(['user_id', 'room_type_id'], 'reviews_user_room_type_unique'));
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $table) => $table->dropUnique('reviews_user_room_type_unique'));
    }
};
