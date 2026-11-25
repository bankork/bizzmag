<?php

namespace Tests\Concurrency;

use PHPUnit\Framework\Attributes\Group;

#[Group('concurrency')]
class IdempotencyConcurrencyTest extends ConcurrencyTestCase
{
    public function test_parallel_retries_with_one_key_run_the_operation_exactly_once(): void
    {
        $outputs = $this->runWorkersTogether('idempotency.php', [['1'], ['2'], ['3'], ['4']]);

        $results = array_map(fn (string $output) => json_decode($output, true, flags: JSON_THROW_ON_ERROR), $outputs);
        $bodies = array_unique(array_map(fn (array $result) => json_encode($result['body']), $results));
        $freshRuns = array_filter($results, fn (array $result) => $result['replayed'] === false);

        $this->assertCount(1, $bodies, 'Every worker must receive the same stored result.');
        $this->assertCount(1, $freshRuns, 'Exactly one worker may run the operation.');
        $this->assertDatabaseCount('audit_events', 1);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }
}
