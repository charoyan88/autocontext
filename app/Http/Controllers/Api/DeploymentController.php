<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deployment;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeploymentController extends Controller
{
    /**
     * Store a new deployment.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Project $project */
        $project = $request->attributes->get('project');

        $validator = Validator::make($request->all(), [
            'version' => 'required|string|max:255',
            'environment' => 'sometimes|string|max:255',
            'region' => 'sometimes|string|max:255',
            'started_at' => 'required|date',
            'finished_at' => 'sometimes|nullable|date|after:started_at',
            'metadata' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        $deployment = Deployment::create([
            'project_id' => $project->id,
            ...$validator->validated()
        ]);

        // Reset deployment-specific error counters for fresh metrics
        app(\App\Services\ErrorAggregator::class)->resetDeploymentCounters($project->id);

        return response()->json([
            'status' => 'created',
            'deployment' => [
                'id' => $deployment->id,
                'version' => $deployment->version,
                'environment' => $deployment->environment,
                'region' => $deployment->region,
                'started_at' => $deployment->started_at->toIso8601String(),
                'finished_at' => $deployment->finished_at?->toIso8601String(),
                'metadata' => $deployment->metadata,
                'created_at' => $deployment->created_at->toIso8601String(),
            ]
        ], 201);
    }
}
