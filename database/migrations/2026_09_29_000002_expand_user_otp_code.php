<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code', 255)->nullable()->change();
        });
    }

    public function down(): void
    {
        $hasLongOtp = DB::table('users')
            ->whereNotNull('otp_code')
            ->pluck('otp_code')
            ->contains(fn (string $otp): bool => strlen($otp) > 10);

        if ($hasLongOtp) {
            throw new \RuntimeException('Cannot shrink users.otp_code without truncating hashed OTP values.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code', 10)->nullable()->change();
        });
    }
};
