<?php

namespace App\Jobs;

use App\DTO\LogEventData;
use App\Models\Project;
use App\Services\LogContextEnricher;
use App\Services\LogFilter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessLogBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $projectId,
        public array $events
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(
        LogContextEnricher $enricher,
        LogFilter $filter,
        \App\Services\LogForwarder $forwarder,
        \App\Services\StatsRecorder $statsRecorder,
        \App\Services\ErrorAggregator $errorAggregator
    ): void {
        $project = Project::find($this->projectId);

        if (!$project) {
            Log::error("Project not found", ['project_id' => $this->projectId]);
            return;
        }

        Log::info("Processing log batch", [
            'project_id' => $this->projectId,
            'project_name' => $project->name,
            'event_count' => count($this->events)
        ]);


        $enrichedEvents = [];

        foreach ($this->events as $event) {
            if (is_array($event)) {
                $event = LogEventData::fromArray($event);
            }

            if (!$event instanceof LogEventData) {
                Log::warning('Skipping invalid log event payload', [
                    'project_id' => $this->projectId,
                ]);
                continue;
            }

            try {
                // Step 1: Record incoming stat
                $statsRecorder->recordIncoming($project->id);

                // Step 2: Enrich event with deployment context
                $enrichedEvent = $enricher->enrich($project, $event);

                // Step 3: Aggregate errors FIRST (before any filtering)
                $level = strtoupper($enrichedEvent->level ?? '');
                $isError = in_array($level, ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']);
                if ($isError) {
                    $errorAggregator->record(
                        $project->id,
                        $enrichedEvent,
                        $enrichedEvent->deploymentId
                    );
                }

                // Step 4: Check for duplicate errors
                if ($filter->shouldDropDuplicate($project->id, $enrichedEvent, $errorAggregator)) {
                    $statsRecorder->recordFiltered($project->id);
                    continue;
                }

                // Step 5: Check if event should be filtered (noise)
                if ($filter->shouldDrop($enrichedEvent)) {
                    $statsRecorder->recordFiltered($project->id);
                    continue;
                }

                // Collect for batch forwarding
                $enrichedEvents[] = $enrichedEvent;

                // Record deployment errors for stats
                if ($isError && $enrichedEvent->deploymentRelated) {
                    $statsRecorder->recordDeploymentError($project->id);
                }

            } catch (\Exception $e) {
                Log::error("Failed to process event in batch", [
                    'project_id' => $this->projectId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Step 6: Forward enriched events batch to downstream
        if (!empty($enrichedEvents)) {
            $hasActiveEndpoints = $project->downstreamEndpoints()->where('is_active', true)->exists();

            if ($hasActiveEndpoints) {
                $queued = $forwarder->forwardBatch($enrichedEvents, $project);

                Log::info("Batch processed and queued for forwarding", [
                    'project_id' => $this->projectId,
                    'accepted_count' => count($enrichedEvents),
                    'queued' => $queued
                ]);
            } else {
                Log::info("Batch processed but no active downstream endpoints. Events dropped.", [
                    'project_id' => $this->projectId,
                    'accepted_count' => count($enrichedEvents)
                ]);
            }
        }
    }
}
