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
        \App\Services\ErrorAggregator $errorAggregator,
        \App\Services\ClickhouseLogWriter $clickhouseWriter
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
        $clickhouseEvents = [];

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

                $isFiltered = false;

                // Step 4: Check for duplicate errors
                if ($filter->shouldDropDuplicate($project->id, $enrichedEvent, $errorAggregator)) {
                    $statsRecorder->recordFiltered($project->id);
                    $isFiltered = true;
                }

                // Step 5: Check if event should be filtered (noise)
                if (!$isFiltered && $filter->shouldDrop($enrichedEvent)) {
                    $statsRecorder->recordFiltered($project->id);
                    $isFiltered = true;
                }

                if (!$isFiltered) {
                    // Collect for batch forwarding
                    $enrichedEvents[] = $enrichedEvent;

                    // Record deployment errors for stats
                    if ($isError && $enrichedEvent->deploymentRelated) {
                        $statsRecorder->recordDeploymentError($project->id);
                    }
                }

                $clickhouseEvents[] = $this->toClickhouseRow($project->id, $enrichedEvent, $isFiltered, $isError);
            } catch (\Exception $e) {
                Log::error("Failed to process event in batch", [
                    'project_id' => $this->projectId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if (!empty($clickhouseEvents)) {
            $clickhouseWriter->writeBatch($project, $clickhouseEvents);
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

    private function toClickhouseRow(int $projectId, LogEventData $event, bool $isFiltered, bool $isError): array
    {
        return [
            'project_id' => $projectId,
            'ts' => $event->timestamp ?? now()->toIso8601String(),
            'level' => strtoupper($event->level ?? 'INFO'),
            'message' => (string) ($event->message ?? ''),
            'service' => (string) ($event->service ?? ''),
            'region' => (string) ($event->region ?? ''),
            'path' => (string) ($event->path ?? ''),
            'deployment_version' => (string) ($event->deploymentVersion ?? ''),
            'deployment_environment' => (string) ($event->deploymentEnvironment ?? ''),
            'deployment_related' => $event->deploymentRelated ? 1 : 0,
            'is_filtered' => $isFiltered ? 1 : 0,
            'is_error' => $isError ? 1 : 0,
            'forward_failed' => 0,
        ];
    }
}
