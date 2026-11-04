<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_idempotency_keys')) {
            return;
        }

        Schema::create('api_idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('actor_scope', 120);
            $table->string('route_signature', 190);
            $table->string('idempotency_key_hash', 64);
            $table->string('request_hash', 64);
            $table->unsignedSmallInteger('status_code')->default(200);
            $table->json('response_body')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['actor_scope', 'route_signature', 'idempotency_key_hash'], 'api_idemp_actor_route_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_idempotency_keys');
    }
};
