<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Thông tin khách
            $table->foreignId('user_id')->nullable()->constrained('users')
                ->onDelete('set null')->onUpdate('cascade');
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone', 30);

            // Thời gian
            $table->date('check_in');
            $table->date('check_out');
            $table->dateTime('actual_check_in')->nullable();
            $table->dateTime('actual_check_out')->nullable();

            // Số khách
            $table->integer('adult_count');
            $table->integer('child_count')->default(0);

            // Giá
            $table->decimal('total_price', 12, 2);

            // Thanh toán — bỏ 'cash', thêm momo/zalopay/vnpay
            $table->enum('payment_method', [
                'vietqr',
                'momo',
                'zalopay',
                'vnpay',
            ])->nullable();

            $table->enum('payment_status', [
                'pending',   // Chờ thanh toán
                'paid',      // Đã thanh toán
                'refunded',  // Đã hoàn tiền
                'failed',    // Thanh toán thất bại
            ])->default('pending');

            // Trạng thái booking
            $table->enum('status', [
                'pending',      // Chờ xác nhận
                'confirmed',    // Đã xác nhận
                'checked_in',   // Đang ở
                'completed',    // Hoàn thành
                'cancelled',    // Đã hủy
            ])->default('pending');

            // Hủy phòng & hoàn tiền
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->enum('refund_status', [
                'none',       // Không hoàn tiền
                'eligible',   // Đủ điều kiện hoàn
                'processing', // Đang xử lý hoàn
                'refunded',   // Đã hoàn
            ])->default('none');
            $table->decimal('refund_amount', 12, 2)->default(0);

            $table->timestamps(); // created_at = booking_date
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
