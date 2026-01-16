<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectHourlyStat;
use App\Models\AggregatedError;

class DashboardService
{
    /**
     * Get project statistics for the last N hours.
     */
    public function getProjectStats(int $projectId, int $hours = 24): array
    {
        $from = now()->subHours($hours);

        $stats = ProjectHourlyStat::where('project_id', $projectId)
            ->where('hour_ts', '>=', $from)
            ->orderBy('hour_ts')
            ->get();

        return [
            'total_incoming' => $stats->sum('incoming_count'),
            'total_outgoing' => $stats->sum('outgoing_count'),
            'total_filtered' => $stats->sum('filtered_count'),
            'total_deployment_errors' => $stats->sum('deployment_error_count'),
            'total_forward_failed' => $stats->sum('forward_failed_count'),
            'savings_percentage' => $this->calculateSavings($stats),
            'hourly_data' => $stats,
        ];
    }

    /**
     * Get chart data for Chart.js.
     */
    public function getHourlyChartData(int $projectId, int $hours = 24): array
    {
        $from = now()->subHours($hours);

        $stats = ProjectHourlyStat::where('project_id', $projectId)
            ->where('hour_ts', '>=', $from)
            ->orderBy('hour_ts')
            ->get();

        return [
            'labels' => $stats->map(fn($s) => $s->hour_ts->format('H:00'))->toArray(),
            'incoming' => $stats->pluck('incoming_count')->toArray(),
            'outgoing' => $stats->pluck('outgoing_count')->toArray(),
            'filtered' => $stats->pluck('filtered_count')->toArray(),
        ];
    }

    /**
     * Get recent deployments.
     */
    public function getRecentDeployments(int $projectId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\Deployment::where('project_id', $projectId)
            ->orderBy('started_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get error counts for deployments.
     */
    public function getDeploymentErrorCountsForWindow(\Illuminate\Support\Collection $deployments): array
    {
        if ($deployments->isEmpty()) {
            return [];
        }

        $windowMinutes = (int) config('log_filter.deployment_window_minutes', 30);
        $counts = [];

        foreach ($deployments as $deployment) {
            $windowEnd = $deployment->started_at->copy()->addMinutes($windowMinutes);

            $count = ProjectHourlyStat::where('project_id', $deployment->project_id)
                ->whereBetween('hour_ts', [$deployment->started_at, $windowEnd])
                ->sum('deployment_error_count');

            $counts[$deployment->id] = (int) $count;
        }

        return $counts;
    }

    /**
     * Get top errors.
     */
    public function getTopErrors(int $projectId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\AggregatedError::where('project_id', $projectId)
            ->orderBy('count_total', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate savings percentage.
     */
    private function calculateSavings($stats): float
    {
        $totalIncoming = $stats->sum('incoming_count');
        $totalOutgoing = $stats->sum('outgoing_count');

        if ($totalIncoming === 0) {
            return 0.0;
        }

        return round((1 - ($totalOutgoing / $totalIncoming)) * 100, 2);
    }
}
