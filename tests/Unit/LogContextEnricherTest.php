<?php

namespace Tests\Unit;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use App\DTO\LogEventData;
use App\Services\LogContextEnricher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogContextEnricherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_events_within_deploy_window(): void
    {
        config(['log_filter.deployment_window_minutes' => 30]);

        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Demo',
            'slug' => 'demo',
            'status' => 'active',
            'default_region' => 'us-east-1',
            'user_id' => $user->id,
        ]);

        $deployment = Deployment::create([
            'project_id' => $project->id,
            'version' => 'v1.0.0',
            'environment' => 'production',
            'region' => 'us-east-1',
            'started_at' => now()->subMinutes(10),
        ]);

        $event = LogEventData::fromArray([
            'timestamp' => now()->toIso8601String(),
            'level' => 'ERROR',
            'message' => 'Test error',
        ]);

        $enricher = new LogContextEnricher();
        $enriched = $enricher->enrich($project, $event);

        $this->assertSame($deployment->id, $enriched->deploymentId);
        $this->assertTrue($enriched->deploymentRelated);
    }

    public function test_it_marks_events_outside_deploy_window(): void
    {
        config(['log_filter.deployment_window_minutes' => 30]);

        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Demo',
            'slug' => 'demo-2',
            'status' => 'active',
            'default_region' => 'us-east-1',
            'user_id' => $user->id,
        ]);

        Deployment::create([
            'project_id' => $project->id,
            'version' => 'v1.0.0',
            'environment' => 'production',
            'region' => 'us-east-1',
            'started_at' => now()->subHours(2),
        ]);

        $event = LogEventData::fromArray([
            'timestamp' => now()->toIso8601String(),
            'level' => 'ERROR',
            'message' => 'Late error',
        ]);

        $enricher = new LogContextEnricher();
        $enriched = $enricher->enrich($project, $event);

        $this->assertFalse($enriched->deploymentRelated);
    }
}
