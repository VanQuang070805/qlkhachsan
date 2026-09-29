<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amenities', function (Blueprint $table) {
            $table->id();
            $table->string('amenity_name', 150);
            $table->string('icon', 50)->nullable(); // icon class (fa-wifi, v.v.)
            $table->timestamps();
        });

        Schema::create('room_type_amenities', function (Blueprint $table) {
            $table->foreignId('room_type_id')->constrained('room_types')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('amenity_id')->constrained('amenities')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->primary(['room_type_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_type_amenities');
        Schema::dropIfExists('amenities');
    }
};
