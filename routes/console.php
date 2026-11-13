<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Automated Scheduled Tasks — WebRajya ERP
|--------------------------------------------------------------------------
| The scheduler is started automatically inside Docker (entrypoint.sh).
| For non-Docker installs, add a cron entry on the CA's machine:
|   * * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
|--------------------------------------------------------------------------
*/

// Auto-backup database every day at 2:00 AM — prevents data loss on crash
Schedule::command('backup:create')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/backup-scheduler.log'));

// Check for software updates every day at 3:00 AM — keeps app current
Schedule::command('update:check')
    ->dailyAt('03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/update-scheduler.log'));

