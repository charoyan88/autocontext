<?php

namespace App\Services;

use App\DTO\LogEventData;
use App\Services\ErrorAggregator;

class LogFilter
{
    /**
     * Determine if a log event should be dropped/filtered.
     */
    public function shouldDrop(LogEventData $event): bool
    {
        // Filter 1: Drop DEBUG and TRACE levels
        if ($this->isNoiseLevel($event)) {
            return true;
        }

        // Filter 2: Drop health-check and monitoring endpoints
        if ($this->isHealthCheckPath($event)) {
            return true;
        }

        // TODO: Filter 3: Drop duplicate errors (Redis-based, Iteration 2)
        // if ($this->isDuplicateError($event)) {
        //     return true;
        // }

        return false;
    }

    /**
     * Check if the event should be dropped as a duplicate error.
     */
    public function shouldDropDuplicate(int $projectId, LogEventData $event, ErrorAggregator $errorAggregator): bool
    {
        $level = strtoupper($event->level ?? '');
        $isError = in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']);

        if (!$isError) {
            return false;
        }

        return $errorAggregator->shouldFilter($projectId, $event);
    }

    /**
     * Check if the event level is considered noise.
     */
    private function isNoiseLevel(LogEventData $event): bool
    {
        $level = strtoupper($event->level ?? '');
        $noiseLevels = config('log_filter.noise_levels', ['DEBUG', 'TRACE']);

        return in_array($level, $noiseLevels);
    }

    /**
     * Check if the event is from a health-check or monitoring endpoint.
     */
    private function isHealthCheckPath(LogEventData $event): bool
    {
        $path = $event->path ?? '';

        if (empty($path)) {
            return false;
        }

        $healthCheckPaths = config('log_filter.health_check_paths', [
            '/health',
            '/ping',
            '/metrics',
            '/status',
            '/healthz',
            '/readiness',
            '/liveness',
        ]);

        foreach ($healthCheckPaths as $healthPath) {
            if (str_starts_with($path, $healthPath)) {
                return true;
            }
        }

        return false;
    }
}
