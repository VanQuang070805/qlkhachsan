<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')->where('status', 'soon_to_checkin')->update(['status' => 'available']);
        DB::table('rooms')->where('status', 'soon_to_checkout')->update(['status' => 'occupied']);
        DB::table('bookings')->where('status', 'soon_to_checkout')->update(['status' => 'checked_in']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','occupied','cleaning','maintenance','booked','overdue') DEFAULT 'available'");
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','checked_in','completed','cancelled') DEFAULT 'pending'");
        }

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dateTime('checkout_due_at')->nullable()->after('check_out');
            $table->decimal('hourly_extension_charge', 12, 2)->default(0)->after('checkout_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn(['checkout_due_at', 'hourly_extension_charge']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','soon_to_checkin','occupied','soon_to_checkout','cleaning','maintenance','booked','overdue') DEFAULT 'available'");
            DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','checked_in','soon_to_checkout','completed','cancelled') DEFAULT 'pending'");
        }
    }
};
