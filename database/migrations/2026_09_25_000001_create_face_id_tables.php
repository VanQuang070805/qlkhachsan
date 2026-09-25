<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('face_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->longText('embedding');
            $table->string('embedding_model', 80)->default('sface_2021dec');
            $table->unsignedSmallInteger('embedding_dimension');
            $table->unsignedSmallInteger('sample_count')->default(15);
            $table->unsignedInteger('version')->default(1);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('consent_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('face_sync_queue', function (Blueprint $table) {
            $table->id();
            $table->uuid('face_profile_id');
            $table->string('action', 10);
            $table->string('status', 12)->default('PENDING')->index();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('next_attempt_at')->nullable()->index();
            $table->timestamp('last_attempt_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('face_profile_id')->references('id')->on('face_profiles')->cascadeOnDelete();
            $table->index(['face_profile_id', 'action', 'status'], 'face_sync_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('face_sync_queue');
        Schema::dropIfExists('face_profiles');
    }
};

