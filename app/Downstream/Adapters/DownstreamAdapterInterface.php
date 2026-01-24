<?php

namespace App\Downstream\Adapters;

use App\Models\DownstreamEndpoint;
use App\Models\Project;

interface DownstreamAdapterInterface
{
    /**
     * Send a batch of events to the downstream endpoint.
     */
    public function sendBatch(array $events, DownstreamEndpoint $endpoint, Project $project): bool;
}
