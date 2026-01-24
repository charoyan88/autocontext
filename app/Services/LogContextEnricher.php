<?php

namespace App\Services;

use App\DTO\LogEventData;
use App\Models\Deployment;
use App\Models\Project;
use Carbon\Carbon;

class LogContextEnricher
{
    /**
     * Time window in minutes after deployment to mark events as deployment-related.
     */
    private const DEPLOYMENT_WINDOW_MINUTES = 30;

    /**
     * Enrich a log event with deployment context and metadata.
     */
    public function enrich(Project $project, LogEventData $event): LogEventData
    {
        // Parse event timestamp
        $eventTimestamp = Carbon::parse($event->timestamp);

        // Find relevant deployment
        $deployment = $this->findRelevantDeployment($project, $eventTimestamp);

        if ($deployment) {
            $event->deploymentId = $deployment->id;
            $event->deploymentVersion = $deployment->version;
            $event->deploymentEnvironment = $deployment->environment;

            // Check if event is deployment-related (within time window)
            $event->deploymentRelated = $this->isDeploymentRelated(
                $deployment,
                $eventTimestamp
            );
        } else {
            $event->deploymentId = null;
            $event->deploymentVersion = null;
            $event->deploymentEnvironment = null;
            $event->deploymentRelated = false;
        }

        // Add service and region if not present
        $event->service = $event->service ?? $project->name;
        $event->region = $event->region ?? $deployment?->region ?? $project->default_region;

        return $event;
    }

    /**
     * Find the most relevant deployment for a given timestamp.
     */
    private function findRelevantDeployment(Project $project, Carbon $timestamp): ?Deployment
    {
        return Deployment::where('project_id', $project->id)
            ->where('started_at', '<=', $timestamp)
            ->where(function ($query) use ($timestamp) {
                $query->whereNull('finished_at')
                    ->orWhere('finished_at', '>=', $timestamp);
            })
            ->orderBy('started_at', 'desc')
            ->first();
    }

    /**
     * Check if an event is deployment-related (within time window after deployment).
     */
    private function isDeploymentRelated(Deployment $deployment, Carbon $eventTimestamp): bool
    {
        $deploymentStart = $deployment->started_at;
        $windowMinutes = (int) config('log_filter.deployment_window_minutes', self::DEPLOYMENT_WINDOW_MINUTES);
        $windowEnd = $deploymentStart->copy()->addMinutes($windowMinutes);

        return $eventTimestamp->between($deploymentStart, $windowEnd);
    }
}
