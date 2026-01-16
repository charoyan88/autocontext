<?php

namespace Database\Seeders;

use App\Models\AggregatedError;
use App\Models\ApiKey;
use App\Models\Deployment;
use App\Models\DownstreamEndpoint;
use App\Models\Project;
use App\Models\ProjectHourlyStat;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
            ]
        );

        // Create a demo project
        $project = Project::firstOrCreate(
            ['slug' => 'demo-app'],
            [
                'name' => 'Demo Application',
                'status' => 'active',
                'default_region' => 'us-east-1',
                'user_id' => $user->id,
            ]
        );
        if ($project->user_id !== $user->id) {
            $project->user_id = $user->id;
            $project->save();
        }

        // Create an API key for the project
        $apiKey = ApiKey::firstOrCreate(
            ['project_id' => $project->id],
            [
                'key' => ApiKey::generateKey(),
                'is_active' => true,
                'description' => 'Demo API Key for testing',
            ]
        );

        // Create some sample deployments
        Deployment::firstOrCreate(
            ['project_id' => $project->id, 'version' => 'v1.0.0'],
            [
                'environment' => 'production',
                'region' => 'us-east-1',
                'started_at' => now()->subHours(2),
                'finished_at' => now()->subHours(2)->addMinutes(5),
                'metadata' => [
                    'commit' => 'abc123',
                    'deployer' => 'CI/CD Pipeline',
                ],
            ]
        );

        $latestDeployment = Deployment::firstOrCreate(
            ['project_id' => $project->id, 'version' => 'v1.1.0'],
            [
                'environment' => 'production',
                'region' => 'us-east-1',
                'started_at' => now()->subMinutes(30),
                'finished_at' => null, // Still in progress
                'metadata' => [
                    'commit' => 'def456',
                    'deployer' => 'CI/CD Pipeline',
                ],
            ]
        );

        // Create downstream endpoints
        DownstreamEndpoint::updateOrCreate(
            ['project_id' => $project->id, 'type' => 'file'],
            [
                'endpoint_url' => null,
                'config' => [
                    'filename' => 'demo-app.log',
                ],
                'is_active' => true,
            ]
        );

        // Generate hourly stats for the last 24 hours
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i)->startOfHour();

            $incoming = rand(1000, 4000);
            $filtered = (int) ($incoming * rand(25, 45) / 100);
            $outgoing = $incoming - $filtered;
            $errors = ($i <= 2 && $i >= 1) ? rand(30, 150) : rand(0, 10);

            ProjectHourlyStat::updateOrCreate(
                ['project_id' => $project->id, 'hour_ts' => $hour],
                [
                    'incoming_count' => $incoming,
                    'outgoing_count' => $outgoing,
                    'filtered_count' => $filtered,
                    'deployment_error_count' => $errors,
                    'forward_failed_count' => rand(0, 3),
                ]
            );
        }

        AggregatedError::updateOrCreate(
            [
                'project_id' => $project->id,
                'error_hash' => md5('Connection timeout'),
            ],
            [
                'last_message' => 'Connection timeout to payment gateway',
                'level' => 'ERROR',
                'last_seen_at' => now()->subMinutes(10),
                'count_total' => 150,
                'count_since_last_deploy' => 150,
                'last_deployment_id' => $latestDeployment->id,
            ]
        );

        AggregatedError::updateOrCreate(
            [
                'project_id' => $project->id,
                'error_hash' => md5('NullPointer'),
            ],
            [
                'last_message' => 'Call to a member function getUrl() on null',
                'level' => 'CRITICAL',
                'last_seen_at' => now()->subHours(1),
                'count_total' => 5,
                'count_since_last_deploy' => 5,
                'last_deployment_id' => $latestDeployment->id,
            ]
        );

        $this->command->info('Demo project created successfully!');
        $this->command->info('Project: ' . $project->name);
        $this->command->info('Login: demo@example.com / password');
        $this->command->info('API Key: ' . $apiKey->key);
        $this->command->info('Downstream endpoint: file (active)');
        $this->command->info('');
        $this->command->info('You can now test the API with:');
        $this->command->info('curl -X POST http://localhost:8080/api/logs \\');
        $this->command->info('  -H "X-Api-Key: ' . $apiKey->key . '" \\');
        $this->command->info('  -H "Content-Type: application/json" \\');
        $this->command->info('  -d \'{"timestamp":"' . now()->toIso8601String() . '","level":"INFO","message":"Test log message"}\'');
    }
}
