<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('bookings', 'checkout_due_at') && Schema::hasColumn('bookings', 'hourly_extension_charge')) {
            DB::table('bookings')
                ->where(function ($query) {
                    $query->whereNotNull('checkout_due_at')->orWhere('hourly_extension_charge', '>', 0);
                })
                ->orderBy('id')
                ->get(['id', 'check_out', 'checkout_due_at', 'hourly_extension_charge', 'payment_method'])
                ->each(function ($booking): void {
                    $reference = 'EXT-MIGRATED-'.$booking->id;
                    if (DB::table('payment_logs')->where('reference_code', $reference)->exists()) {
                        return;
                    }

                    $dueAt = $booking->checkout_due_at ?: $booking->check_out.' 18:00:00';
                    DB::table('payment_logs')->insert([
                        'booking_id' => $booking->id,
                        'gateway' => in_array($booking->payment_method, ['cash', 'vietqr', 'momo', 'zalopay', 'vnpay'], true)
                            ? $booking->payment_method : 'cash',
                        'transaction_id' => null,
                        'reference_code' => $reference,
                        'amount' => max(0, (float) $booking->hourly_extension_charge),
                        'purpose' => 'stay_extension',
                        'status' => 'pending',
                        'raw_response' => json_encode([
                            'mode' => 'hours',
                            'checkout_date' => date('Y-m-d', strtotime($booking->check_out)),
                            'due_at' => date('c', strtotime($dueAt)),
                            'migrated_from_temporary_booking_columns' => true,
                        ], JSON_THROW_ON_ERROR),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }

        if (Schema::hasColumn('bookings', 'checkout_due_at') || Schema::hasColumn('bookings', 'hourly_extension_charge')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $columns = array_values(array_filter(['checkout_due_at', 'hourly_extension_charge'], fn ($column) => Schema::hasColumn('bookings', $column)));
                if ($columns) $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('bookings', 'checkout_due_at') || ! Schema::hasColumn('bookings', 'hourly_extension_charge')) {
            Schema::table('bookings', function (Blueprint $table): void {
                if (! Schema::hasColumn('bookings', 'checkout_due_at')) $table->dateTime('checkout_due_at')->nullable()->after('check_out');
                if (! Schema::hasColumn('bookings', 'hourly_extension_charge')) $table->decimal('hourly_extension_charge', 12, 2)->default(0)->after('checkout_due_at');
            });
        }

        DB::table('bookings')->orderBy('id')->get(['id', 'check_out'])->each(function ($booking): void {
            $logs = DB::table('payment_logs')
                ->where('booking_id', $booking->id)
                ->where('purpose', 'stay_extension')
                ->where('status', 'pending')
                ->orderBy('id')
                ->get(['amount', 'raw_response']);
            $current = $logs->filter(function ($log) use ($booking) {
                $metadata = is_array($log->raw_response) ? $log->raw_response : json_decode($log->raw_response ?? '[]', true);
                return ($metadata['mode'] ?? null) === 'hours'
                    && ($metadata['checkout_date'] ?? null) === date('Y-m-d', strtotime($booking->check_out));
            });
            $latest = $current->last();
            if (! $latest) return;

            $metadata = is_array($latest->raw_response) ? $latest->raw_response : json_decode($latest->raw_response ?? '[]', true);
            DB::table('bookings')->where('id', $booking->id)->update([
                'checkout_due_at' => $metadata['due_at'] ?? null,
                'hourly_extension_charge' => $current->sum('amount'),
            ]);
        });
    }
};
