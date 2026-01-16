<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class StatsRecorder
{
    /**
     * Record an incoming log event.
     */
    public function recordIncoming(int $projectId): void
    {
        $this->increment($projectId, 'incoming');
    }

    /**
     * Record an outgoing log event.
     */
    public function recordOutgoing(int $projectId): void
    {
        $this->increment($projectId, 'outgoing');
    }

    /**
     * Record a filtered log event.
     */
    public function recordFiltered(int $projectId): void
    {
        $this->increment($projectId, 'filtered');
    }

    /**
     * Record a deployment-related error.
     */
    public function recordDeploymentError(int $projectId): void
    {
        $this->increment($projectId, 'deployment_errors');
    }

    /**
     * Record a downstream forwarding failure.
     */
    public function recordForwardFailed(int $projectId): void
    {
        $this->increment($projectId, 'forward_failed');
    }

    /**
     * Increment a counter in Redis.
     */
    private function increment(int $projectId, string $type): void
    {
        $hour = now()->format('YmdH');
        $key = "stats:{$projectId}:{$hour}:{$type}";

        Redis::incr($key);

        // Set expiration to 48 hours to prevent memory leak
        Redis::expire($key, 48 * 3600);
    }

    /**
     * Get stats for a specific hour.
     */
    public function getHourlyStats(int $projectId, string $hour): array
    {
        $types = ['incoming', 'outgoing', 'filtered', 'deployment_errors', 'forward_failed'];
        $stats = [];

        foreach ($types as $type) {
            $key = "stats:{$projectId}:{$hour}:{$type}";
            $stats[$type] = (int) Redis::get($key) ?: 0;
        }

        return $stats;
    }

    /**
     * Get all stat keys for a project within a time range.
     */
    public function getStatKeys(int $projectId, ?\DateTime $from = null, ?\DateTime $to = null): array
    {
        $pattern = "stats:{$projectId}:*";
        $keys = Redis::keys($pattern);

        if ($from || $to) {
            $keys = array_filter($keys, function ($key) use ($from, $to) {
                // Extract hour from key: stats:1:2025121014:incoming -> 2025121014
                preg_match('/stats:\d+:(\d{10})/', $key, $matches);
                if (!isset($matches[1])) {
                    return false;
                }

                $keyHour = $matches[1];

                if ($from && $keyHour < $from->format('YmdH')) {
                    return false;
                }

                if ($to && $keyHour > $to->format('YmdH')) {
                    return false;
                }

                return true;
            });
        }

        return array_values($keys);
    }
}
