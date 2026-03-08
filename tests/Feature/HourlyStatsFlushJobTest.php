<?php

namespace Tests\Feature;

use App\Jobs\HourlyStatsFlushJob;
use App\Models\Project;
use App\Models\ProjectHourlyStat;
use App\Models\User;
use App\Services\StatsRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class HourlyStatsFlushJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_flush_parses_prefixed_keys_and_writes_stats(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 26, 9, 30, 0));

        $user = User::factory()->create();
        $project = Project::create([
            'name' => 'Test Project',
            'slug' => 'test-project',
            'user_id' => $user->id,
            'status' => 'active',
            'default_region' => 'us-east-1',
        ]);

        $hour = now()->format('YmdH');
        $prefixedIncoming = "laravel-database-stats:{$project->id}:{$hour}:incoming";
        $prefixedOutgoing = "laravel-database-stats:{$project->id}:{$hour}:outgoing";

        Redis::shouldReceive('scan')
            ->once()
            ->with(0, ['match' => 'stats:*', 'count' => 100])
            ->andReturn([0, [$prefixedIncoming, $prefixedOutgoing]]);

        Redis::shouldReceive('get')
            ->with("stats:{$project->id}:{$hour}:incoming")
            ->andReturn(3);
        Redis::shouldReceive('get')
            ->with("stats:{$project->id}:{$hour}:outgoing")
            ->andReturn(2);

        Redis::shouldReceive('del')
            ->times(5)
            ->andReturn(1);

        $job = new HourlyStatsFlushJob();
        $job->handle(app(StatsRecorder::class));

        $stat = ProjectHourlyStat::where('project_id', $project->id)->first();
        $this->assertNotNull($stat);
        $this->assertSame(3, $stat->incoming_count);
        $this->assertSame(2, $stat->outgoing_count);
        $this->assertSame(0, $stat->filtered_count);
    }
}
