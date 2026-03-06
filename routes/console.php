<?php

use App\Jobs\CleanupExpiredTokensJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// T084: Schedule token cleanup job to run daily at 2 AM
Schedule::job(new CleanupExpiredTokensJob())->dailyAt('02:00');
