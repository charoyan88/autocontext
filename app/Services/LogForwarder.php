<?php

namespace App\Services;

use App\Models\DownstreamEndpoint;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

        $endpoints = $project->downstreamEndpoints()
            ->where('is_active', true)
            ->get();

        if ($endpoints->isEmpty()) {
            return false;
        }

        $allSuccess = true;

        foreach ($endpoints as $endpoint) {
            try {
                $result = match ($endpoint->type) {
                    'http' => $this->forwardHttpBatch($events, $endpoint),
                    'file' => $this->forwardFileBatch($events, $endpoint, $project),
                    'sentry' => $this->forwardSentryBatch($events, $endpoint),
                    default => false,
                };

                if (!$result) {
                    $allSuccess = false;
                }
            } catch (\Exception $e) {
                Log::error('Failed to forward batch', [
                    'project_id' => $project->id,
                    'endpoint_id' => $endpoint->id,
                    'error' => $e->getMessage(),
                ]);
                $allSuccess = false;
            }
        }

        return $allSuccess;
    }

    /**
     * Forward event via HTTP POST.
     */
    private function forwardHttpBatch(array $events, DownstreamEndpoint $endpoint): bool
    {
        $config = $endpoint->config ?? [];
        $headers = $config['headers'] ?? [];
        $timeout = $config['timeout'] ?? 5;

        // For generic HTTP, we send the whole array if it's a batch
        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->post($endpoint->endpoint_url, ['events' => $events]);

        return $response->successful();
    }

    /**
     * Forward to Sentry using their Store API format.
     */
    private function forwardSentryBatch(array $events, DownstreamEndpoint $endpoint): bool
    {
        $config = $endpoint->config ?? [];
        $dsn = $endpoint->endpoint_url; // Assuming DSN is stored here for Sentry type

        if (empty($dsn)) {
            return false;
        }

        $allSuccessful = true;

        foreach ($events as $event) {
            $sentryEvent = [
                'event_id' => bin2hex(random_bytes(16)),
                'timestamp' => $event['timestamp'],
                'level' => strtolower($event['level']),
                'message' => $event['message'],
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

            $response = Http::withHeaders($config['headers'] ?? [])
                ->post($dsn, $sentryEvent);

            if (!$response->successful()) {
                $allSuccessful = false;
                Log::warning("Failed to send event to Sentry", [
                    'project_id' => $endpoint->project_id,
                    'status' => $response->status()
                ]);
            }
        }

        return $allSuccessful;
    }

    /**
     * Forward events to file in batch.
     */
    private function forwardFileBatch(array $events, DownstreamEndpoint $endpoint, Project $project): bool
    {
        $config = $endpoint->config ?? [];
        $filename = $config['filename'] ?? "downstream_{$project->slug}.log";
        $path = "downstream/{$filename}";

        $directory = dirname(storage_path("logs/{$path}"));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $content = "";
        foreach ($events as $event) {
            $content .= json_encode($event) . PHP_EOL;
        }

        return (bool) file_put_contents(
            storage_path("logs/{$path}"),
            $content,
            FILE_APPEND
        );
    }
}
