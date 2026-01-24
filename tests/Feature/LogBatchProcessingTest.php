<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\DownstreamEndpoint;
use App\Models\ApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class LogBatchProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_logs_are_accepted_and_job_is_dispatched()
    {
        Bus::fake();

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $project = Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'user_id' => $user->id,
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        $apiKey = ApiKey::create([
            'project_id' => $project->id,
            'key' => ApiKey::generateKey(),
            'is_active' => true,
        ]);

        $payload = [
            'events' => [
                ['timestamp' => now()->toIso8601String(), 'level' => 'INFO', 'message' => 'Test log 1'],
                ['timestamp' => now()->toIso8601String(), 'level' => 'INFO', 'message' => 'Test log 2'],
            ]
        ];

        $response = $this->postJson('/api/logs', $payload, [
            'X-API-Key' => $apiKey->key
        ]);

        $response->assertStatus(202);
        Bus::assertDispatched(\App\Jobs\ProcessLogBatchJob::class);
    }

    public function test_forwarder_sends_batch_to_http()
    {
        Http::fake();

        $user = \App\Models\User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $project = Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'user_id' => $user->id,
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        DownstreamEndpoint::create([
            'project_id' => $project->id,
            'type' => 'http',
            'endpoint_url' => 'https://example.com/webhook',
            'is_active' => true,
        ]);

        $forwarder = app(\App\Services\LogForwarder::class);
        $events = [
            ['timestamp' => '2026-01-17T12:00:00Z', 'level' => 'INFO', 'message' => 'Log 1'],
            ['timestamp' => '2026-01-17T12:00:01Z', 'level' => 'INFO', 'message' => 'Log 2'],
        ];

        $forwarder->forwardBatch($events, $project);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://example.com/webhook' &&
                count($request->data()['events']) === 2;
        });
    }

    public function test_sentry_formatting_is_applied()
    {
        Http::fake();

        $user = \App\Models\User::create([
            'name' => 'Test User 2',
            'email' => 'test2@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $project = Project::create([
            'name' => 'Test Project 2',
            'slug' => 'test-project-2',
            'user_id' => $user->id,
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        DownstreamEndpoint::create([
            'project_id' => $project->id,
            'type' => 'sentry',
            'endpoint_url' => 'https://sentry.io/api/123/store/',
            'is_active' => true,
        ]);

        $forwarder = app(\App\Services\LogForwarder::class);

        $events = [
            [
                'timestamp' => '2026-01-17T12:00:00Z',
                'level' => 'ERROR',
                'message' => 'Sentry test error',
                'context' => ['user_id' => 123],
                'deployment_version' => 'v1.0.0',
                'deployment_environment' => 'prod',
                'region' => 'us-east-1',
                'deployment_related' => true,
            ]
        ];

        $forwarder->forwardBatch($events, $project);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return $data['level'] === 'error' &&
                $data['message'] === 'Sentry test error' &&
                $data['release'] === 'v1.0.0' &&
                $data['tags']['region'] === 'us-east-1';
        });
    }

    public function test_no_active_endpoints_returns_false()
    {
        $user = \App\Models\User::create([
            'name' => 'Test User 3',
            'email' => 'test3@example.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
        ]);

        $project = Project::create([
            'name' => 'Test Project 3',
            'slug' => 'test-project-3',
            'user_id' => $user->id,
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        $forwarder = app(\App\Services\LogForwarder::class);
        $result = $forwarder->forwardBatch([['msg' => 'hi']], $project);

        $this->assertFalse($result);
    }
}
