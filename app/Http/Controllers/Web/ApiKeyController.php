<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    /**
     * Generate a new API key for the project.
     */
    public function store(Request $request, Project $project)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(404);
        }

        // MVP Limit: Max 3 API keys per project
        if ($project->apiKeys()->count() >= 3) {
            return redirect()->back()->withErrors(['key' => 'MVP Limit: Maximum 3 API keys per project.']);
        }
        ApiKey::create([
            'project_id' => $project->id,
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'description' => 'Generated via Dashboard',
        ]);

        return redirect()->back()->with('success', 'New API Key generated successfully.');
    }

    /**
     * Revoke (delete) an API key.
     */
    public function destroy(Project $project, ApiKey $apiKey)
    {
        if ($project->user_id !== auth()->id()) {
            abort(404);
        }

        // Ensure the key belongs to the project
        if ($apiKey->project_id !== $project->id) {
            abort(403, 'Unauthorized action.');
        }

        $apiKey->delete();

        return redirect()->back()->with('success', 'API Key revoked successfully.');
    }

    /**
     * Activate or deactivate an API key.
     */
    public function update(Request $request, Project $project, ApiKey $apiKey)
    {
        if ($project->user_id !== $request->user()->id) {
            abort(404);
        }

        if ($apiKey->project_id !== $project->id) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $apiKey->update(['is_active' => $validated['is_active']]);

        $message = $validated['is_active'] ? 'API Key activated successfully.' : 'API Key deactivated successfully.';

        return redirect()->back()->with('success', $message);
    }
}
