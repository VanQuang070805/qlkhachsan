<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            if (Schema::hasTable('rooms')) {
                DB::table('rooms')->whereNull('status')->update(['status' => 'available']);
                DB::statement("ALTER TABLE rooms MODIFY COLUMN status ENUM('available','occupied','cleaning','maintenance','booked','overdue') NOT NULL DEFAULT 'available'");
            }

            if (Schema::hasTable('bookings')) {
                DB::table('bookings')->whereNull('status')->update(['status' => 'pending']);
                DB::statement("ALTER TABLE bookings MODIFY COLUMN status ENUM('pending','confirmed','checked_in','completed','cancelled') NOT NULL DEFAULT 'pending'");
            }

            if (Schema::hasTable('face_profiles') && Schema::hasColumn('face_profiles', 'consent_at')) {
                DB::statement('ALTER TABLE face_profiles MODIFY COLUMN consent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
            }

            if (Schema::hasTable('refund_transactions') && Schema::hasColumn('refund_transactions', 'processed_at')) {
                DB::statement('ALTER TABLE refund_transactions MODIFY COLUMN processed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
            }

            if (Schema::hasTable('api_idempotency_keys') && Schema::hasColumn('api_idempotency_keys', 'expires_at')) {
                DB::statement('ALTER TABLE api_idempotency_keys MODIFY COLUMN expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
            }
        } elseif ($driver === 'sqlite') {
            // Relax legacy SQLite enum check on rooms.status so 'overdue' is accepted identically to MySQL/MariaDB.
            if (Schema::hasTable('rooms')) {
                Schema::table('rooms', function (Blueprint $table): void {
                    $table->string('status', 32)->default('available')->change();
                });
            }
            if (Schema::hasTable('bookings')) {
                Schema::table('bookings', function (Blueprint $table): void {
                    $table->string('status', 32)->default('pending')->change();
                });
            }
        }

        if (Schema::hasTable('bookings')) {
            $this->addIndexIfMissing('bookings', ['status', 'check_in', 'check_out'], 'bookings_status_checkin_checkout_idx');
            $this->addIndexIfMissing('bookings', ['user_id', 'status'], 'bookings_user_status_idx');
        }

        if (Schema::hasTable('rooms')) {
            $this->addIndexIfMissing('rooms', ['room_type_id', 'status'], 'rooms_type_status_idx');
            $this->addIndexIfMissing('rooms', ['floor', 'room_number'], 'rooms_floor_number_idx');
        }

        if (Schema::hasTable('payment_logs')) {
            $this->addIndexIfMissing('payment_logs', ['booking_id', 'purpose', 'status'], 'payment_logs_booking_purpose_status_idx');
            $this->addUniqueIfMissing('payment_logs', ['gateway', 'transaction_id'], 'payment_logs_gateway_txn_unique');
        }

        if (Schema::hasTable('price_settings')) {
            $this->addIndexIfMissing('price_settings', ['status', 'start_date', 'end_date'], 'price_settings_status_dates_idx');
        }

        if (Schema::hasTable('holidays') && Schema::hasColumn('holidays', 'start_date') && Schema::hasColumn('holidays', 'end_date')) {
            $this->addIndexIfMissing('holidays', ['start_date', 'end_date'], 'holidays_dates_idx');
        }
    }

    public function down(): void
    {
        $this->dropIndexIfExists('bookings', 'bookings_status_checkin_checkout_idx');
        $this->dropIndexIfExists('bookings', 'bookings_user_status_idx');
        $this->dropIndexIfExists('rooms', 'rooms_type_status_idx');
        $this->dropIndexIfExists('rooms', 'rooms_floor_number_idx');
        $this->dropIndexIfExists('payment_logs', 'payment_logs_booking_purpose_status_idx');
        $this->dropUniqueIfExists('payment_logs', 'payment_logs_gateway_txn_unique');
        $this->dropIndexIfExists('price_settings', 'price_settings_status_dates_idx');
        $this->dropIndexIfExists('holidays', 'holidays_dates_idx');
    }

    private function addIndexIfMissing(string $table, array $columns, string $name): void
    {
        if (Schema::hasIndex($table, $name)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }

    private function addUniqueIfMissing(string $table, array $columns, string $name): void
    {
        if (Schema::hasIndex($table, $name)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->unique($columns, $name);
        });
    }

    private function dropIndexIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropIndex($name);
        });
    }

    private function dropUniqueIfExists(string $table, string $name): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
            return;
        }
        Schema::table($table, function (Blueprint $blueprint) use ($name): void {
            $blueprint->dropUnique($name);
        });
    }
};
