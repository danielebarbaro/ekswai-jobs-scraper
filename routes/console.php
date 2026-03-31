<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily job sync to run every day at 9 AM
Schedule::command('jobs:sync-daily')
    ->dailyAt('09:00')
    ->timezone('UTC')
    ->withoutOverlapping()
    ->onOneServer()
    ->runInBackground();
