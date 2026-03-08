<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/logs', [
            'timestamp' => now()->toIso8601String(),
            'level' => 'INFO',
            'message' => 'Test log',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'API key is required']);
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $response = $this->postJson('/api/logs', [
            'timestamp' => now()->toIso8601String(),
            'level' => 'INFO',
            'message' => 'Test log',
        ], [
            'X-Api-Key' => 'ak_invalid',
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['error' => 'Invalid API key']);
    }

    public function test_inactive_project_returns_403(): void
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Inactive Project',
            'slug' => 'inactive-project',
            'user_id' => $user->id,
            'status' => 'inactive',
            'default_region' => 'us-east-1',
        ]);

        $apiKey = ApiKey::create([
            'project_id' => $project->id,
            'key' => ApiKey::generateKey(),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/logs', [
            'timestamp' => now()->toIso8601String(),
            'level' => 'INFO',
            'message' => 'Test log',
        ], [
            'X-Api-Key' => $apiKey->key,
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['error' => 'Project inactive']);
    }
}
