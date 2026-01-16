<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     */
    public function index()
    {
        $projects = Project::withCount(['apiKeys', 'deployments'])
            ->where('user_id', auth()->id())
            ->get();
        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create()
    {
        return view('projects.create');
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request)
    {
        $userId = $request->user()->id;
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:projects',
            'default_region' => 'required|string|max:255',
        ]);

        $validated['status'] = 'active';
        $validated['user_id'] = $userId;

        // MVP Limit: Max 5 projects allowed globally
        if (Project::where('user_id', $userId)->count() >= 5) {
            return redirect()->back()->withErrors(['name' => 'MVP Limit: Maximum 5 projects allowed.']);
        }

        $project = Project::create($validated);

        // Auto-create first API key
        ApiKey::create([
            'project_id' => $project->id,
            'key' => ApiKey::generateKey(),
            'is_active' => true,
            'description' => 'Initial API Key',
        ]);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified project.
     */
    public function show(Project $project)
    {
        $this->ensureOwnership($project);
        $project->load(['apiKeys', 'downstreamEndpoint', 'deployments' => fn($q) => $q->latest()->limit(10)]);
        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified project.
     */
    public function edit(Project $project)
    {
        $this->ensureOwnership($project);
        return view('projects.edit', compact('project'));
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, Project $project)
    {
        $this->ensureOwnership($project);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_region' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $project->update($validated);

        return redirect()->route('projects.show', $project)
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Remove the specified project.
     */
    public function destroy(Project $project)
    {
        $this->ensureOwnership($project);
        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully!');
    }

    private function ensureOwnership(Project $project): void
    {
        if ($project->user_id !== auth()->id()) {
            abort(404);
        }
    }
}
