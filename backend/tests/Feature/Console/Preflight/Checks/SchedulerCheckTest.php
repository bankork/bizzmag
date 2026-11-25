<?php

namespace Tests\Feature\Console\Preflight\Checks;

use App\Console\Preflight\Checks\SchedulerCheck;
use App\Console\Preflight\CheckStatus;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SchedulerCheckTest extends TestCase
{
    public function test_warns_when_the_scheduler_has_never_run(): void
    {
        $result = $this->app->make(SchedulerCheck::class)->run()[0];

        $this->assertSame(CheckStatus::Warn, $result->status);
        $this->assertStringContainsString('schedule:run', (string) $result->fix);
    }

    public function test_passes_with_a_recent_heartbeat(): void
    {
        $this->travelTo('2026-11-25 10:00:00');
        Cache::forever(config('bizzmag.scheduler.heartbeat_cache_key'), '2026-11-25T09:58:00+00:00');

        $result = $this->app->make(SchedulerCheck::class)->run()[0];

        $this->assertSame(CheckStatus::Pass, $result->status);
        $this->assertSame('Last heartbeat 2 minute(s) ago', $result->detail);
    }

    public function test_fails_in_production_when_cron_has_stopped(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $this->travelTo('2026-11-25 10:00:00');
        Cache::forever(config('bizzmag.scheduler.heartbeat_cache_key'), '2026-11-25T09:30:00+00:00');

        $result = $this->app->make(SchedulerCheck::class)->run()[0];

        $this->assertSame(CheckStatus::Fail, $result->status);
    }
}
