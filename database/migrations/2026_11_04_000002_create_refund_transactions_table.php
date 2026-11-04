<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained('bookings')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->decimal('amount', 12, 2);
            $table->string('provider', 32)->default('manual');
            $table->string('provider_reference', 120)->unique();
            $table->string('status', 24)->default('completed');
            $table->timestamp('processed_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_transactions');
    }
};
