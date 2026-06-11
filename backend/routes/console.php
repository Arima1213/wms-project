<?php
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('wms:check-expiry')->daily()->withoutOverlapping();
Schedule::command('wms:stock-alerts')->hourly()->withoutOverlapping();
Schedule::command('webhooks:process-retries')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('notifications:check')->hourly()->withoutOverlapping();
