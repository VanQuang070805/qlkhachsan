<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:snapshot')->dailyAt('00:10')->withoutOverlapping();
Schedule::command('bookings:expire-pending')->everyMinute()->withoutOverlapping();
Schedule::command('bookings:expire-no-shows')->everyMinute()->withoutOverlapping();
Schedule::command('face:sync')->everyMinute()->withoutOverlapping();
