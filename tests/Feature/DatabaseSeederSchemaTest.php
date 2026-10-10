<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_seeder_writes_catalog_and_internal_accounts_without_sample_transactions(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(DB::table('price_settings')
            ->where('name', 'Dịp lễ Tết')
            ->where('adjustment_type', 'percent')
            ->where('adjustment_value', 50)
            ->where('status', true)
            ->exists());
        $this->assertSame(50, DB::table('rooms')->count());
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertDatabaseCount('booking_rooms', 0);
        $this->assertDatabaseCount('payment_logs', 0);
        $this->assertDatabaseCount('reviews', 0);
        $this->assertTrue(Schema::hasTable('user_permissions'));
        $this->assertTrue(Schema::hasTable('price_setting_room_types'));
        $this->assertTrue(Schema::hasColumn('price_settings', 'holiday_id'));
    }

    public function test_demo_seed_rolls_back_all_rows_when_a_later_insert_fails(): void
    {
        $failureInjected = false;
        DB::listen(function (QueryExecuted $query) use (&$failureInjected): void {
            if (! $failureInjected && str_contains(strtolower($query->sql), 'insert into') && str_contains(strtolower($query->sql), 'price_settings')) {
                $failureInjected = true;
                throw new RuntimeException('Simulated seed failure.');
            }
        });

        try {
            $this->seed(DatabaseSeeder::class);
            $this->fail('The simulated failure should interrupt seeding.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated seed failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('room_types', 0);
    }
}
