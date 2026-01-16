<?php

namespace App\Services;

use App\Models\AggregatedError;
use Illuminate\Support\Facades\Redis;

class ErrorAggregator
{
    /**
     * Record an error event and check if it should be filtered.
     */
    public function shouldFilter(int $projectId, array $event): bool
    {
        $errorHash = $this->calculateErrorHash($event);

        // Use event timestamp, not worker time
        $eventTime = isset($event['timestamp'])
            ? \Carbon\Carbon::parse($event['timestamp'])
            : now();
        $minute = $eventTime->format('YmdHi');

        $key = "err:{$projectId}:{$errorHash}:{$minute}";

        $count = Redis::incr($key);
        Redis::expire($key, 120); // Keep for 2 minutes

        $threshold = config('log_filter.duplicate_error_threshold', 10);

        return $count > $threshold;
    }

    /**
     * Record error in database for aggregation.
     */
    public function record(int $projectId, array $event, ?int $deploymentId = null): void
    {
        $errorHash = $this->calculateErrorHash($event);

        AggregatedError::updateOrCreate(
            [
                'project_id' => $projectId,
                'error_hash' => $errorHash,
            ],
            [
                'last_message' => $event['message'] ?? '',
                'level' => $event['level'] ?? 'ERROR',
                'last_seen_at' => now(),
                'last_deployment_id' => $deploymentId,
                'sample_event' => $event,
            ]
        )->increment('count_total');

        // If there's a deployment, also increment deployment-specific counter
        if ($deploymentId) {
            AggregatedError::where('project_id', $projectId)
                ->where('error_hash', $errorHash)
                ->increment('count_since_last_deploy');
        }
    }

    /**
     * Calculate error hash from event.
     */
    private function calculateErrorHash(array $event): string
    {
        $components = [
            $event['level'] ?? '',
            $event['message'] ?? '',
            $event['context']['exception'] ?? '',
            $event['context']['file'] ?? '',
            $event['context']['line'] ?? '',
        ];

        return md5(implode('|', $components));
    }

    /**
     * Reset deployment counters for a project.
     */
    public function resetDeploymentCounters(int $projectId): void
    {
        AggregatedError::where('project_id', $projectId)
            ->update(['count_since_last_deploy' => 0]);
    }
}
