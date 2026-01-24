<?php

namespace App\Jobs;

use App\Downstream\Adapters\DownstreamAdapterInterface;
use App\Models\DownstreamEndpoint;
use App\Models\Project;
use App\Services\StatsRecorder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDownstreamBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $projectId,
        public int $endpointId,
        public array $events
    ) {
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }

    /**
     * Execute the job.
     */
    public function handle(StatsRecorder $statsRecorder): void
    {
        $project = Project::find($this->projectId);
        $endpoint = DownstreamEndpoint::find($this->endpointId);

        if (!$project || !$endpoint || (int) $endpoint->project_id !== (int) $project->id) {
            Log::warning('Downstream send skipped due to missing project/endpoint', [
                'project_id' => $this->projectId,
                'endpoint_id' => $this->endpointId,
            ]);
            return;
        }

        $adapter = $this->resolveAdapter($endpoint->type);
        if (!$adapter) {
            Log::warning('No downstream adapter registered', [
                'project_id' => $project->id,
                'endpoint_id' => $endpoint->id,
                'type' => $endpoint->type,
            ]);
            return;
        }

        $result = $adapter->sendBatch($this->events, $endpoint, $project);
        if (!$result) {
            throw new \RuntimeException('Downstream batch send failed');
        }

        for ($i = 0; $i < count($this->events); $i++) {
            $statsRecorder->recordOutgoing($project->id);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $projectId = $this->projectId;
        $eventCount = count($this->events);

        Log::error('Downstream batch send failed after retries', [
            'project_id' => $projectId,
            'endpoint_id' => $this->endpointId,
            'error' => $exception->getMessage(),
        ]);

        $statsRecorder = app(StatsRecorder::class);
        for ($i = 0; $i < $eventCount; $i++) {
            $statsRecorder->recordForwardFailed($projectId);
        }
    }

    private function resolveAdapter(string $type): ?DownstreamAdapterInterface
    {
        $adapters = config('downstream.adapters', []);
        $adapterClass = $adapters[$type] ?? null;

        if (!$adapterClass || !class_exists($adapterClass)) {
            return null;
        }

        return app($adapterClass);
    }
}
