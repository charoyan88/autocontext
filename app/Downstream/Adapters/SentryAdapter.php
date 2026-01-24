<?php

namespace App\Downstream\Adapters;

use App\Models\DownstreamEndpoint;
use App\Models\Project;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SentryAdapter implements DownstreamAdapterInterface
{
    public function sendBatch(array $events, DownstreamEndpoint $endpoint, Project $project): bool
    {
        $dsn = $endpoint->endpoint_url;
        if (empty($dsn)) {
            return false;
        }

        $config = $endpoint->config ?? [];
        $headers = $config['headers'] ?? [];
        $allSuccessful = true;

        $responses = Http::pool(function (Pool $pool) use ($headers, $dsn, $events) {
            foreach ($events as $index => $event) {
                $pool->as((string) $index)
                    ->withHeaders($headers)
                    ->post($dsn, $this->formatEvent($event));
            }
        });

        foreach ($responses as $response) {
            if (!$response->successful()) {
                $allSuccessful = false;
                Log::warning('Failed to send event to Sentry', [
                    'project_id' => $endpoint->project_id,
                    'status' => $response->status(),
                ]);
            }
        }

        return $allSuccessful;
    }

    private function formatEvent(array $event): array
    {
        return [
            'event_id' => bin2hex(random_bytes(16)),
            'timestamp' => $event['timestamp'] ?? now()->toIso8601String(),
            'level' => strtolower($event['level'] ?? 'error'),
            'message' => $event['message'] ?? '',
            'platform' => 'php',
            'server_name' => $event['service'] ?? 'auto-context',
            'release' => $event['deployment_version'] ?? null,
            'environment' => $event['deployment_environment'] ?? 'production',
            'tags' => [
                'region' => $event['region'] ?? 'unknown',
                'deployment_related' => (string) ($event['deployment_related'] ?? 'false'),
            ],
            'extra' => $event['context'] ?? [],
        ];
    }
}
