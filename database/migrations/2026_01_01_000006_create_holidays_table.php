<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng ngày lễ — dùng để xác định rule hoàn tiền khi hủy phòng
        // Hủy trước 3 ngày nếu check-in rơi vào ngày lễ → hoàn 100%
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);         // Tên ngày lễ (VD: Tết Nguyên Đán)
            $table->date('date');                 // Ngày cụ thể
            $table->boolean('recurring')->default(true); // Lặp lại hàng năm?
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
