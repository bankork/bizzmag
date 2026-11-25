<?php

namespace App\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\PreflightCheck;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;
use Throwable;

/**
 * Confirms cron is running `schedule:run`, using the heartbeat the
 * scheduler writes every minute (routes/console.php).
 */
final class SchedulerCheck implements PreflightCheck
{
    public function __construct(private readonly Repository $cache) {}

    public function run(): array
    {
        // In the release layout, cron must call current/artisan, never a timestamped
        // release folder that will be deleted after later deployments.
        $artisan = preg_replace('#/releases/[^/]+$#', '/current', base_path()).'/artisan';
        $cronLine = '* * * * * '.escapeshellarg(PHP_BINARY).' '.escapeshellarg($artisan).' schedule:run >> /dev/null 2>&1';

        try {
            $heartbeat = $this->cache->get(config('bizzmag.scheduler.heartbeat_cache_key'));
        } catch (Throwable) {
            return [CheckResult::fail('Scheduler (cron)', 'The cache store cannot be read.', 'Run php artisan migrate --force so the cache table exists.')];
        }

        if ($heartbeat === null) {
            return [CheckResult::warn('Scheduler (cron)', 'No heartbeat yet. Normal right after deployment; otherwise cron is not running.', "Add this cron job: {$cronLine}")];
        }

        $age = (int) CarbonImmutable::parse($heartbeat)->diffInMinutes(now(), absolute: true);
        $maximum = (int) config('bizzmag.scheduler.heartbeat_max_age_minutes');

        if ($age <= $maximum) {
            return [CheckResult::pass('Scheduler (cron)', "Last heartbeat {$age} minute(s) ago")];
        }

        $detail = "Last heartbeat {$age} minutes ago. Cron appears to have stopped.";

        return app()->isProduction()
            ? [CheckResult::fail('Scheduler (cron)', $detail, "Check the cron job: {$cronLine}")]
            : [CheckResult::warn('Scheduler (cron)', $detail, "Check the cron job: {$cronLine}")];
    }
}
