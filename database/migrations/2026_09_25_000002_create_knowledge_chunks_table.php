<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('source');
            $table->string('title');
            $table->text('content');
            $table->char('content_hash', 64)->unique();
            $table->json('embedding')->nullable();
            $table->string('embedding_model')->nullable();
            $table->timestamps();
            $table->index(['source', 'title']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_chunks');
    }
};
