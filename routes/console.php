<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('bookings:expire-pending')->everyMinute()->withoutOverlapping();
Schedule::command('bookings:expire-no-shows')->everyMinute()->withoutOverlapping();
Schedule::command('bookings:sync-overdue')->everyMinute()->withoutOverlapping();
Schedule::command('face:sync')->everyMinute()->withoutOverlapping();
Schedule::call(function (): void {
    if (\Illuminate\Support\Facades\Schema::hasTable('api_idempotency_keys')) {
        \Illuminate\Support\Facades\DB::table('api_idempotency_keys')->where('expires_at', '<=', now())->delete();
    }
    if (\Illuminate\Support\Facades\Schema::hasTable('integration_outbox')) {
        \Illuminate\Support\Facades\DB::table('integration_outbox')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now()->subDays(7))
            ->delete();
    }
})->hourly()->name('prune-transient-integration-tables')->withoutOverlapping();
