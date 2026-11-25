<?php

namespace Tests\Concurrency;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

/**
 * Base class for tests that start several real PHP processes at once.
 *
 * Workers need committed data, so these tests truncate instead of wrapping
 * each test in a transaction.
 */
abstract class ConcurrencyTestCase extends TestCase
{
    use DatabaseTruncation;

    /**
     * Run a worker script in several processes that all start at the same instant.
     *
     * @param  list<list<string>>  $argumentsPerWorker
     * @return list<string> Each worker's standard output, in worker order.
     */
    protected function runWorkersTogether(string $script, array $argumentsPerWorker): array
    {
        $connection = config('database.connections.'.config('database.default'));
        $environment = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => config('database.default'),
            'DB_HOST' => (string) $connection['host'],
            'DB_PORT' => (string) $connection['port'],
            'DB_DATABASE' => (string) $connection['database'],
            'DB_USERNAME' => (string) $connection['username'],
            'DB_PASSWORD' => (string) $connection['password'],
            'CACHE_STORE' => 'array',
            'CONCURRENCY_START_AT' => (string) (microtime(true) + 2.0),
        ];

        $workerPath = __DIR__.'/workers/'.$script;

        $results = Process::pool(function (Pool $pool) use ($argumentsPerWorker, $environment, $workerPath): void {
            foreach ($argumentsPerWorker as $index => $arguments) {
                $pool->as((string) $index)
                    ->env($environment)
                    ->timeout(60)
                    ->command([PHP_BINARY, $workerPath, ...$arguments]);
            }
        })->start()->wait();

        $outputs = [];

        foreach (array_keys($argumentsPerWorker) as $index) {
            $result = $results[(string) $index];
            $this->assertTrue($result->successful(), "Worker {$index} failed: ".$result->errorOutput());
            $outputs[] = $result->output();
        }

        return $outputs;
    }
}
