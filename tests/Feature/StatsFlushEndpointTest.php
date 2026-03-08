<?php

namespace Tests\Feature;

use App\Jobs\HourlyStatsFlushJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class StatsFlushEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_trigger_manual_flush(): void
    {
        $user = User::factory()->create();

        $called = false;
        $job = new class($called) extends HourlyStatsFlushJob {
            public function __construct(private bool &$calledRef)
            {
            }

            public function handle(\App\Services\StatsRecorder $statsRecorder): void
            {
                $this->calledRef = true;
            }
        };
        $this->app->instance(HourlyStatsFlushJob::class, $job);

        $response = $this->actingAs($user)
            ->withoutMiddleware(ValidateCsrfToken::class)
            ->post(route('stats.flush'));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Stats flushed successfully.');
        $this->assertTrue($called);
    }
}
