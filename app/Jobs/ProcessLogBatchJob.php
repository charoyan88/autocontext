<?php

namespace App\Jobs;

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


        foreach ($this->events as $event) {
            try {
                // Step 1: Record incoming stat
                $statsRecorder->recordIncoming($project->id);

                // Step 2: Enrich event with deployment context
                $enrichedEvent = $enricher->enrich($project, $event);

                // Step 3: Aggregate errors FIRST (before any filtering)
                $isError = in_array(strtoupper($enrichedEvent['level'] ?? ''), ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY']);
                if ($isError) {
                    $errorAggregator->record(
                        $project->id,
                        $enrichedEvent,
                        $enrichedEvent['deployment_id'] ?? null
                    );
                }

                // Step 4: Check for duplicate errors (if ERROR level)
                if ($filter->shouldDropDuplicate($project->id, $enrichedEvent, $errorAggregator)) {
                    Log::debug("Duplicate error filtered", [
                        'project_id' => $this->projectId,
                        'message' => substr($enrichedEvent['message'] ?? '', 0, 100)
                    ]);
                    $statsRecorder->recordFiltered($project->id);
                    continue;
                }

                // Step 5: Check if event should be filtered (noise)
                if ($filter->shouldDrop($enrichedEvent)) {
                    Log::debug("Event filtered", [
                        'project_id' => $this->projectId,
                        'level' => $enrichedEvent['level'] ?? 'unknown',
                        'message' => substr($enrichedEvent['message'] ?? '', 0, 100)
                    ]);
                    $statsRecorder->recordFiltered($project->id);
                    continue;
                }

                // Step 6: Forward event to downstream
                $forwarded = false;
                $hasActiveDownstream = $project->downstreamEndpoints()
                    ->where('is_active', true)
                    ->exists();

                if ($hasActiveDownstream) {
                    $forwarded = $forwarder->forward($enrichedEvent, $project);
                }

                if ($forwarded) {
                    $statsRecorder->recordOutgoing($project->id);
                } elseif ($hasActiveDownstream) {
                    $statsRecorder->recordForwardFailed($project->id);
                }

                // Step 7: Record deployment errors
                if ($isError && ($enrichedEvent['deployment_related'] ?? false)) {
                    $statsRecorder->recordDeploymentError($project->id);
                }

                Log::info("Event processed successfully", [
                    'project_id' => $this->projectId,
                    'level' => $enrichedEvent['level'] ?? 'unknown',
                    'deployment_id' => $enrichedEvent['deployment_id'] ?? null,
                    'forwarded' => $forwarded
                ]);

            } catch (\Exception $e) {
                Log::error("Failed to process event", [
                    'project_id' => $this->projectId,
                    'error' => $e->getMessage(),
                    'event' => $event
                ]);
            }
        }
    }
}
