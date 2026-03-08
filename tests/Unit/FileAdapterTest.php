<?php

namespace Tests\Unit;

use App\Downstream\Adapters\FileAdapter;
use App\Models\DownstreamEndpoint;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_adapter_writes_events_to_storage(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'user_id' => $user->id,
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        $endpoint = DownstreamEndpoint::create([
            'project_id' => $project->id,
            'type' => 'file',
            'endpoint_url' => null,
            'is_active' => true,
            'config' => ['filename' => 'test-downstream.log'],
        ]);

        $adapter = new FileAdapter();
        $events = [
            ['timestamp' => '2026-01-26T09:00:00Z', 'level' => 'INFO', 'message' => 'Hello'],
            ['timestamp' => '2026-01-26T09:00:01Z', 'level' => 'ERROR', 'message' => 'Boom'],
        ];

        $result = $adapter->sendBatch($events, $endpoint, $project);

        $this->assertTrue($result);

        $path = storage_path('logs/downstream/test-downstream.log');
        $this->assertFileExists($path);

        $content = file_get_contents($path);
        $this->assertStringContainsString('"message":"Hello"', $content);
        $this->assertStringContainsString('"message":"Boom"', $content);

        @unlink($path);
    }
}
