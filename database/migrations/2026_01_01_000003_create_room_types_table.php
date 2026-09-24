<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('type_name', 100);
            $table->decimal('price', 12, 2);
            $table->integer('max_adults');
            $table->integer('max_children')->default(0);
            $table->integer('max_guests');
            $table->text('description')->nullable();
            $table->string('image')->nullable(); // ảnh đại diện loại phòng
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
