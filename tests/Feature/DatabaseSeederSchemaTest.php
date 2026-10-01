<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_writes_price_data_to_the_current_schema(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(DB::table('price_settings')
            ->where('name', 'Dịp lễ Tết')
            ->where('adjustment_type', 'percent')
            ->where('adjustment_value', 50)
            ->where('status', true)
            ->exists());
        $this->assertSame(25, DB::table('rooms')->count());
        $this->assertSame(2, DB::table('booking_rooms')->count());
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
