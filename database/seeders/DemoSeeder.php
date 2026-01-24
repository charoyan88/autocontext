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
        // 1. Create Admin User
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Demo Admin',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Create Demo Project
        $project = Project::firstOrCreate(
            ['slug' => 'demo-project'],
            [
                'name' => 'Demo E-Commerce App',
                'status' => 'active',
                'default_region' => 'us-east-1',
                'user_id' => $user->id,
            ]
        );
        if ($project->user_id !== $user->id) {
            $project->user_id = $user->id;
            $project->save();
        }

        // 3. Create API Key
        $apiKey = ApiKey::firstOrCreate(
            ['project_id' => $project->id],
            [
                'key' => ApiKey::generateKey(),
                'is_active' => true,
                'description' => 'Demo Key',
                'last_used_at' => now()->subMinutes(5),
            ]
        );

        // 4. Create Downstream Endpoint
        DownstreamEndpoint::updateOrCreate(
            ['project_id' => $project->id],
            [
                'type' => 'file',
                'config' => ['path' => 'demo.log'],
                'is_active' => true,
                'endpoint_url' => null,
            ]
        );

        // 5. Create Deployments
        $deploy1 = Deployment::firstOrCreate(
            ['version' => 'v1.0.0', 'project_id' => $project->id],
            [
                'environment' => 'production',
                'region' => 'us-east-1',
                'started_at' => now()->subDays(2),
                'finished_at' => now()->subDays(2)->addMinutes(5),
            ]
        );

        $deploy2 = Deployment::firstOrCreate(
            ['version' => 'v1.1.0-hotfix', 'project_id' => $project->id],
            [
                'environment' => 'production',
                'region' => 'us-east-1',
                'started_at' => now()->subHours(2),
                'finished_at' => now()->subHours(2)->addMinutes(3),
            ]
        );

        // 6. Generate Hourly Stats
        for ($i = 23; $i >= 0; $i--) {
            $hour = now()->subHours($i)->startOfHour();
            $incoming = rand(1000, 5000);
            $filtered = (int) ($incoming * rand(30, 40) / 100);
            $outgoing = $incoming - $filtered;
            $errors = ($i <= 2 && $i >= 1) ? rand(50, 200) : rand(0, 10);

            ProjectHourlyStat::updateOrCreate(
                ['project_id' => $project->id, 'hour_ts' => $hour],
                [
                    'incoming_count' => $incoming,
                    'outgoing_count' => $outgoing,
                    'filtered_count' => $filtered,
                    'deployment_error_count' => $errors,
                ]
            );
        }

        // 7. Create Aggregated Errors
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
                'last_deployment_id' => $deploy2->id,
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
                'last_deployment_id' => $deploy2->id,
            ]
        );

        if ($this->command) {
            $this->command->info('Demo data seeded successfully!');
            $this->command->info('Login with: admin@example.com / password');
            $this->command->info('API Key: ' . $apiKey->key);
        }
    }
}
