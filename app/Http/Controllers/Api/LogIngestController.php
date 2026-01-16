<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessLogBatchJob;
use App\Models\Project;
use App\Services\LogNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogIngestController extends Controller
{
    /**
     * Ingest logs from client applications.
     */
    public function ingest(Request $request, LogNormalizer $normalizer): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        // Check if it's a batch or single event
        $isBatch = $request->has('events');
        $events = $isBatch ? $request->input('events', []) : [$request->all()];
        $events = $normalizer->normalize($events);

        // Validate events
        $validator = Validator::make(['events' => $events], [
            'events' => 'required|array|min:1',
            'events.*.timestamp' => 'required|date',
            'events.*.level' => 'required|string|in:DEBUG,TRACE,INFO,NOTICE,WARNING,ERROR,CRITICAL,ALERT,EMERGENCY',
            'events.*.message' => 'required|string',
            'events.*.context' => 'sometimes|array',
            'events.*.service' => 'sometimes|string',
            'events.*.region' => 'sometimes|string',
            'events.*.path' => 'sometimes|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $validEvents = $validator->validated()['events'];
        $acceptedCount = count($validEvents);

        // Dispatch job to process logs asynchronously
        ProcessLogBatchJob::dispatch($project->id, $validEvents);

        return response()->json([
            'status' => 'accepted',
            'accepted_count' => $acceptedCount,
            'message' => "Accepted {$acceptedCount} event(s) for processing"
        ], 202);
    }
}
