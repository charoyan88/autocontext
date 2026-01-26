<?php

use App\Jobs\HourlyStatsFlushJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Schedule hourly stats flush job
Schedule::job(new HourlyStatsFlushJob())->everyMinute();
