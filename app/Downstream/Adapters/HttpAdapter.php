<?php

namespace App\Downstream\Adapters;

use App\Models\DownstreamEndpoint;
use App\Models\Project;
use Illuminate\Support\Facades\Http;

class HttpAdapter implements DownstreamAdapterInterface
{
    public function sendBatch(array $events, DownstreamEndpoint $endpoint, Project $project): bool
    {
        if (empty($endpoint->endpoint_url)) {
            return false;
        }

        $config = $endpoint->config ?? [];
        $headers = $config['headers'] ?? [];
        $timeout = $config['timeout'] ?? 5;

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->post($endpoint->endpoint_url, ['events' => $events]);

        return $response->successful();
    }
}
