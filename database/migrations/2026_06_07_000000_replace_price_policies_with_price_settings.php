<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Xóa bảng price_policies cũ nếu tồn tại
        Schema::dropIfExists('price_policies');

        // Tạo bảng price_settings mới từ letan
        Schema::create('price_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('adjustment_type', ['percent', 'fixed']);
            $table->decimal('adjustment_value', 10, 2);
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_settings');

        // Khôi phục lại cấu trúc bảng price_policies cũ của hoaii nếu rollback
        Schema::create('price_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('multiplier', 5, 2);
            $table->timestamps();
        });
    }
};
