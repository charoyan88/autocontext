<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Log;

class ClickhouseLogWriter
{
    public function __construct(private ClickhouseClient $client)
    {
    }

    public function writeBatch(Project $project, array $events): void
    {
        if (!config('clickhouse.enabled')) {
            return;
        }

        try {
            $this->client->insertJsonEachRow('logs_raw', $events);
        } catch (\Throwable $e) {
            Log::warning('ClickHouse write failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
