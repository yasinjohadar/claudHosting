<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('backup:run-scheduled')->everyMinute()->withoutOverlapping()->runInBackground();
Schedule::command('coolify:run-scheduled-snapshots')->hourly()->withoutOverlapping()->runInBackground();
Schedule::command('coolify:run-restore-drills')->weeklyOn(0, '04:00')->withoutOverlapping()->runInBackground();
Schedule::command('coolify:check-ops-alerts')->everyFifteenMinutes()->withoutOverlapping()->runInBackground();
Schedule::command('backup:cleanup-expired')->daily()->at('02:00')->withoutOverlapping();
// Inert until the subscription_expiring template is activated, so scheduling it is safe.
Schedule::command('whatsapp:send-expiry-reminders')->dailyAt('09:00')->withoutOverlapping()->runInBackground();
Schedule::command('infrastructure:record-vps-metrics')->everyFiveMinutes()->withoutOverlapping()->runInBackground();
Schedule::command('infrastructure:prune-vps-metrics')->daily()->at('03:30')->withoutOverlapping();
