<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status 
            ENUM('available','soon_to_checkin','occupied','soon_to_checkout','cleaning','maintenance','booked','overdue')
            DEFAULT 'available'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE rooms MODIFY COLUMN status 
            ENUM('available','soon_to_checkin','occupied','soon_to_checkout','cleaning','maintenance')
            DEFAULT 'available'");
    }
};
