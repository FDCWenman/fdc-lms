<?php

use App\Jobs\CleanupExpiredTokens;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule token cleanup job to run daily at 2 AM
Schedule::job(new CleanupExpiredTokens)->dailyAt('02:00');
