<?php

use App\Domain\Shared\Idempotency\IdempotencyKey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled tasks
|--------------------------------------------------------------------------
|
| One cron entry runs `php artisan schedule:run` every minute (Stage 3 §4.6).
| On shared hosting the scheduler also runs a short queue worker, because
| there is no process supervisor. Set QUEUE_WORK_VIA_SCHEDULER=false on a VPS
| where a supervised worker already runs.
|
*/

Schedule::call(function (): void {
    Cache::forever(config('bizzmag.scheduler.heartbeat_cache_key'), now()->toIso8601String());
})->everyMinute()->name('scheduler-heartbeat');

if (config('bizzmag.scheduler.queue_work_via_scheduler')) {
    Schedule::command('queue:work', ['--stop-when-empty', '--max-time=50', '--tries=3'])
        ->everyMinute()
        ->withoutOverlapping(5)
        ->name('queue-worker');
}

Schedule::command('model:prune', ['--model' => [IdempotencyKey::class]])
    ->daily()
    ->name('prune-idempotency-keys');

if (filled(config('bizzmag.scheduler.heartbeat_url'))) {
    Schedule::call(function (): void {
        Http::timeout(5)->get(config('bizzmag.scheduler.heartbeat_url'));
    })->everyFiveMinutes()->name('external-heartbeat');
}
