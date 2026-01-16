<?php

namespace App\Services;

use App\Models\DownstreamEndpoint;
use App\Models\Project;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LogForwarder
{
    /**
     * Forward a log event to downstream endpoints.
     */
    public function forward(array $event, Project $project): bool
    {
        $endpoints = $project->downstreamEndpoints()
            ->where('is_active', true)
            ->get();

        if ($endpoints->isEmpty()) {
            Log::debug('No active downstream endpoints', ['project_id' => $project->id]);
            return false;
        }

        $success = false;

        foreach ($endpoints as $endpoint) {
            try {
                $result = match ($endpoint->type) {
                    'http' => $this->forwardHttp($event, $endpoint),
                    'file' => $this->forwardFile($event, $endpoint, $project),
                    's3' => $this->forwardS3($event, $endpoint),
                    default => false,
                };

                if ($result) {
                    $success = true;
                    Log::debug('Event forwarded successfully', [
                        'project_id' => $project->id,
                        'endpoint_type' => $endpoint->type,
                        'endpoint_id' => $endpoint->id,
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to forward event', [
                    'project_id' => $project->id,
                    'endpoint_type' => $endpoint->type,
                    'endpoint_id' => $endpoint->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $success;
    }

    /**
     * Forward event via HTTP POST.
     */
    private function forwardHttp(array $event, DownstreamEndpoint $endpoint): bool
    {
        $config = $endpoint->config ?? [];
        $headers = $config['headers'] ?? [];
        $timeout = $config['timeout'] ?? 5;

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->post($endpoint->endpoint_url, $event);

        return $response->successful();
    }

    /**
     * Forward event to file.
     */
    private function forwardFile(array $event, DownstreamEndpoint $endpoint, Project $project): bool
    {
        $config = $endpoint->config ?? [];
        $filename = $config['filename'] ?? "downstream_{$project->slug}.log";
        $path = "downstream/{$filename}";

        // Ensure directory exists
        $directory = dirname(storage_path("logs/{$path}"));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $jsonLine = json_encode($event) . PHP_EOL;

        return (bool) file_put_contents(
            storage_path("logs/{$path}"),
            $jsonLine,
            FILE_APPEND
        );
    }

    /**
     * Forward event to S3 (placeholder for future implementation).
     */
    private function forwardS3(array $event, DownstreamEndpoint $endpoint): bool
    {
        // TODO: Implement S3 forwarding in future iteration
        Log::warning('S3 forwarding not yet implemented', [
            'endpoint_id' => $endpoint->id,
        ]);

        return false;
    }
}
