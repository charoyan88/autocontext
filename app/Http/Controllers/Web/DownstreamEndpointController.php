<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DownstreamEndpoint;
use App\Models\Project;
use Illuminate\Http\Request;

class DownstreamEndpointController extends Controller
{
    /**
     * Update or create the downstream configuration for a project.
     */
    public function update(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(404);
        }

        $allowedTypes = array_keys(config('downstream.adapters', []));

        $validated = $request->validate([
            'type' => 'required|in:' . implode(',', $allowedTypes),
            'endpoint_url' => 'nullable|url|required_if:type,http',
            'config_json' => 'nullable|json',
            'is_active' => 'boolean',
        ]);

        $config = [];
        if (!empty($validated['config_json'])) {
            $config = json_decode($validated['config_json'], true);
        }

        DownstreamEndpoint::updateOrCreate(
            ['project_id' => $project->id],
            [
                'type' => $validated['type'],
                'endpoint_url' => $validated['endpoint_url'],
                'config' => $config,
                'is_active' => $request->has('is_active'),
            ]
        );

        return redirect()->back()->with('success', 'Downstream configuration updated.');
    }
}
