<?php

namespace App\Jobs;

use App\Models\ProjectHourlyStat;
use App\Services\StatsRecorder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class HourlyStatsFlushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(StatsRecorder $statsRecorder): void
    {
        Log::info('Starting hourly stats flush');

        // Get all stat keys from last 2 hours
        $from = now()->subHours(2);
        $to = now();

        $fromHour = $from->format('YmdH');
        $toHour = $to->format('YmdH');

        // Group stats by project and hour
        $statsByProjectHour = [];

        // Scan for all stats keys
        $pattern = 'stats:*';
        $cursor = 0;

        do {
            $result = Redis::scan($cursor, ['match' => $pattern, 'count' => 100]);
            if ($result === false) {
                Log::warning('Redis scan failed during hourly stats flush, falling back to KEYS', [
                    'cursor' => $cursor,
                ]);
                $keys = Redis::keys($pattern);
                $cursor = 0;
            } else {
                $cursor = $result[0] ?? 0;
                $keys = $result[1] ?? [];
            }

            foreach ($keys as $key) {
                // Parse key: stats:1:2025121014:incoming
                if (preg_match('/^stats:(\d+):(\d{10}):(\w+)$/', $key, $matches)) {
                    $projectId = (int) $matches[1];
                    $hour = $matches[2];
                    $type = $matches[3];
                    if ($hour < $fromHour || $hour > $toHour) {
                        continue;
                    }

                    $value = (int) Redis::get($key);

                    if (!isset($statsByProjectHour[$projectId])) {
                        $statsByProjectHour[$projectId] = [];
                    }

                    if (!isset($statsByProjectHour[$projectId][$hour])) {
                        $statsByProjectHour[$projectId][$hour] = [
                            'incoming_count' => 0,
                            'outgoing_count' => 0,
                            'filtered_count' => 0,
                            'deployment_error_count' => 0,
                            'forward_failed_count' => 0,
                        ];
                    }

                    $statsByProjectHour[$projectId][$hour][$type . '_count'] = $value;
                }
            }
        } while ($cursor != 0);

        // Upsert to database
        $flushedCount = 0;
        foreach ($statsByProjectHour as $projectId => $hours) {
            foreach ($hours as $hour => $stats) {
                try {
                    // Parse hour string to datetime
                    $hourTs = \DateTime::createFromFormat('YmdH', $hour);
                    if (!$hourTs) {
                        Log::warning('Invalid hour format', ['hour' => $hour]);
                        continue;
                    }

                    ProjectHourlyStat::updateOrCreate(
                        [
                            'project_id' => $projectId,
                            'hour_ts' => $hourTs,
                        ],
                        $stats
                    );

                    $flushedCount++;

                    // Delete Redis keys after successful flush
                    foreach (['incoming', 'outgoing', 'filtered', 'deployment_errors', 'forward_failed'] as $type) {
                        $key = "stats:{$projectId}:{$hour}:{$type}";
                        Redis::del($key);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to flush stats', [
                        'project_id' => $projectId,
                        'hour' => $hour,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::info('Hourly stats flush completed', [
            'flushed_count' => $flushedCount,
        ]);
    }
}
