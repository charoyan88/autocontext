<?php

namespace Tests\Unit;

use App\DTO\LogEventData;
use App\Services\LogFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_debug_level_is_filtered(): void
    {
        $filter = new LogFilter();
        $event = LogEventData::fromArray([
            'timestamp' => now()->toIso8601String(),
            'level' => 'DEBUG',
            'message' => 'debug noise',
        ]);

        $this->assertTrue($filter->shouldDrop($event));
    }

    public function test_health_check_path_is_filtered(): void
    {
        $filter = new LogFilter();
        $event = LogEventData::fromArray([
            'timestamp' => now()->toIso8601String(),
            'level' => 'INFO',
            'message' => 'health check',
            'path' => '/health',
        ]);

        $this->assertTrue($filter->shouldDrop($event));
    }

    public function test_info_event_is_not_filtered(): void
    {
        $filter = new LogFilter();
        $event = LogEventData::fromArray([
            'timestamp' => now()->toIso8601String(),
            'level' => 'INFO',
            'message' => 'user login',
            'path' => '/login',
        ]);

        $this->assertFalse($filter->shouldDrop($event));
    }
}
