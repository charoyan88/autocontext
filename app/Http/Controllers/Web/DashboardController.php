<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {
    }

    /**
     * Show projects overview.
     */
    public function index()
    {
        $projects = Project::withCount(['apiKeys', 'deployments', 'downstreamEndpoints'])
            ->where('user_id', auth()->id())
            ->get();

        return view('dashboard.index', compact('projects'));
    }

    /**
     * Show project-specific dashboard.
     */
    public function project(Project $project)
    {
        if ($project->user_id !== auth()->id()) {
            abort(404);
        }

        $stats = $this->dashboardService->getProjectStats($project->id, 24);
        $chartData = $this->dashboardService->getHourlyChartData($project->id, 24);
        $recentDeployments = $this->dashboardService->getRecentDeployments($project->id, 5);
        $deploymentErrorCounts = $this->dashboardService->getDeploymentErrorCountsForWindow($recentDeployments);
        $topErrors = $this->dashboardService->getTopErrors($project->id, 5);

        return view('dashboard.project', compact(
            'project',
            'stats',
            'chartData',
            'recentDeployments',
            'deploymentErrorCounts',
            'topErrors'
        ));
    }
}
