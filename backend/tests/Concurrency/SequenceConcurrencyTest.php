<?php

namespace Tests\Concurrency;

use PHPUnit\Framework\Attributes\Group;

#[Group('concurrency')]
class SequenceConcurrencyTest extends ConcurrencyTestCase
{
    public function test_parallel_workers_never_share_a_number_and_rollbacks_leave_no_gaps(): void
    {
        $workers = 4;
        $iterations = 25;

        $outputs = $this->runWorkersTogether('sequence.php', array_fill(0, $workers, [(string) $iterations]));

        $numbers = array_merge(...array_map(fn (string $output) => json_decode($output, true, flags: JSON_THROW_ON_ERROR), $outputs));
        sort($numbers);
        $expectedCommits = $workers * ($iterations - intdiv($iterations, 5));

        $this->assertCount($expectedCommits, $numbers);
        $this->assertSame(range(1, $expectedCommits), $numbers);
    }
}
