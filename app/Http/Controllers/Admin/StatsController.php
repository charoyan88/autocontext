<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\HourlyStatsFlushJob;
use App\Services\StatsRecorder;

class StatsController extends Controller
{
    public function flush()
    {
        $job = app(HourlyStatsFlushJob::class);
        $job->handle(app(StatsRecorder::class));

        return back()->with('success', 'Stats flushed successfully.');
    }
}
