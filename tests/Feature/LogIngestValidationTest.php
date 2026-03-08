<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogIngestValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeApiKey(): ApiKey
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'user_id' => $user->id,
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        return ApiKey::create([
            'project_id' => $project->id,
            'key' => ApiKey::generateKey(),
            'is_active' => true,
        ]);
    }

    public function test_missing_required_fields_returns_422(): void
    {
        $apiKey = $this->makeApiKey();

        $response = $this->postJson('/api/logs', [
            'level' => 'INFO',
            'message' => 'Missing timestamp',
        ], [
            'X-Api-Key' => $apiKey->key,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Validation failed']);
    }

    public function test_invalid_level_returns_422(): void
    {
        $apiKey = $this->makeApiKey();

        $response = $this->postJson('/api/logs', [
            'timestamp' => now()->toIso8601String(),
            'level' => 'SILLY',
            'message' => 'Invalid level',
        ], [
            'X-Api-Key' => $apiKey->key,
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'Validation failed']);
    }
}
