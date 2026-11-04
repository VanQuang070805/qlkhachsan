<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('integration_outbox')) {
            return;
        }

        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 64)->unique();
            $table->string('topic', 120)->index();
            $table->string('event_key', 120)->nullable()->index();
            $table->string('event_type', 120)->index();
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->string('correlation_id', 64)->nullable()->index();
            $table->json('payload');
            $table->timestamp('available_at')->useCurrent()->index();
            $table->timestamp('published_at')->nullable()->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['published_at', 'available_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_outbox');
    }
};
