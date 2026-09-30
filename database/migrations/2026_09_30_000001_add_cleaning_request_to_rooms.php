<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('needs_cleaning')->default(false)->after('status');
            $table->timestamp('cleaning_requested_at')->nullable()->after('needs_cleaning');
        });

        DB::table('rooms')
            ->where('status', 'cleaning')
            ->update([
                'needs_cleaning' => true,
                'cleaning_requested_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['needs_cleaning', 'cleaning_requested_at']);
        });
    }
};
