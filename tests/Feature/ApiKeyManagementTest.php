<?php

namespace Tests\Feature;

use App\Models\ApiKey;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_generate_api_key()
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('projects.api-keys.store', $project));

        $response->assertRedirect();
        $this->assertDatabaseCount('api_keys', 1); // 0 initial + 1 new
        $this->assertDatabaseHas('api_keys', [
            'project_id' => $project->id,
            'description' => 'Generated via Dashboard',
        ]);
    }

    public function test_user_can_revoke_api_key()
    {
        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        $apiKey = ApiKey::create([
            'project_id' => $project->id,
            'key' => ApiKey::generateKey(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->delete(route('projects.api-keys.destroy', [$project, $apiKey]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('api_keys', ['id' => $apiKey->id]);
    }
}
