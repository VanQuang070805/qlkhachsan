<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reviews')) {
            if (! Schema::hasIndex('reviews', 'reviews_user_booking_room_type_idx')) {
                Schema::table('reviews', function (Blueprint $table): void {
                    $table->index(['user_id', 'booking_id', 'room_type_id'], 'reviews_user_booking_room_type_idx');
                });
            }

            if (Schema::hasIndex('reviews', 'reviews_user_booking_room_type_unique')) {
                Schema::table('reviews', function (Blueprint $table): void {
                    $table->dropUnique('reviews_user_booking_room_type_unique');
                });
            }
        }

        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table): void {
                if (! Schema::hasColumn('bookings', 'refund_bank_name')) {
                    $table->string('refund_bank_name', 120)->nullable()->after('refund_amount');
                }
                if (! Schema::hasColumn('bookings', 'refund_account_number')) {
                    $table->string('refund_account_number', 50)->nullable()->after('refund_bank_name');
                }
                if (! Schema::hasColumn('bookings', 'refund_account_holder')) {
                    $table->string('refund_account_holder', 150)->nullable()->after('refund_account_number');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('bookings')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $dropCols = array_values(array_filter(
                    ['refund_bank_name', 'refund_account_number', 'refund_account_holder'],
                    fn (string $col) => Schema::hasColumn('bookings', $col)
                ));
                if ($dropCols !== []) {
                    $table->dropColumn($dropCols);
                }
            });
        }
    }
};
