<?php

namespace App\Services;

use App\DTO\LogEventData;
use App\Jobs\SendDownstreamBatchJob;
use App\Models\Project;

class LogForwarder
{
    /**
     * Forward a batch of log events to downstream endpoints.
     */
    public function forwardBatch(array $events, Project $project): bool
    {
        if (empty($events)) {
            return false;
        }

        $normalizedEvents = array_map(function ($event) {
            if ($event instanceof LogEventData) {
                return $event->toArray();
            }

            return is_array($event) ? $event : [];
        }, $events);

        $normalizedEvents = array_filter($normalizedEvents, static fn(array $event) => !empty($event));

        if (empty($normalizedEvents)) {
            return false;
        }

        $endpoints = $project->downstreamEndpoints()
            ->where('is_active', true)
            ->get();

        if ($endpoints->isEmpty()) {
            return false;
        }

        foreach ($endpoints as $endpoint) {
            SendDownstreamBatchJob::dispatch($project->id, $endpoint->id, $normalizedEvents);
        }

        return true;
    }
}
