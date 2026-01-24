<?php

namespace App\Downstream\Adapters;

use App\Models\DownstreamEndpoint;
use App\Models\Project;

class FileAdapter implements DownstreamAdapterInterface
{
    public function sendBatch(array $events, DownstreamEndpoint $endpoint, Project $project): bool
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
