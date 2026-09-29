<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chi tiết phòng trong booking
        Schema::create('booking_rooms', function (Blueprint $table) {
            $table->foreignId('booking_id')->constrained('bookings')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('room_id')->constrained('rooms')
                ->onUpdate('cascade');
            $table->primary(['booking_id', 'room_id']);
        });

        // Đánh giá
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained('room_types')->onDelete('cascade');
            $table->integer('rating'); // 1-5
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // Chính sách giá theo thời điểm
        Schema::create('price_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_name', 100);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('multiplier', 5, 2); // VD: 1.2 = +20%
            $table->timestamps();
        });

        // Log giao dịch thanh toán (MỚI)
        // Dùng để polling VietQR, lưu webhook MoMo/ZaloPay/VNPay
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->enum('gateway', ['vietqr', 'momo', 'zalopay', 'vnpay']);
            $table->string('transaction_id')->nullable(); // Mã GD từ cổng thanh toán
            $table->string('reference_code')->nullable(); // Nội dung chuyển khoản (dùng cho VietQR matching)
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'success', 'failed']);
            $table->json('raw_response')->nullable(); // Raw JSON từ webhook/API
            $table->timestamps();

            $table->index('reference_code'); // Index để polling nhanh
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
        Schema::dropIfExists('price_policies');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('booking_rooms');
    }
};
