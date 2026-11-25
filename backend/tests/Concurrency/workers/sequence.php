<?php

/**
 * Takes numbers from one sequence in a loop. Every fifth transaction is
 * rolled back deliberately, to prove rollbacks leave no gaps.
 *
 * Only the simulated failure is caught. Any database error (deadlock,
 * lock timeout) makes the worker fail, so the test cannot hide it.
 *
 * Prints a JSON list of the numbers this worker committed.
 */

use App\Domain\Shared\Sequences\Sequence;
use Illuminate\Support\Facades\DB;

$app = require __DIR__.'/bootstrap.php';

final class SimulatedBusinessFailure extends Exception {}

$sequence = $app->make(Sequence::class);
$iterations = (int) ($argv[1] ?? 25);
$committed = [];

waitForStart();

for ($i = 0; $i < $iterations; $i++) {
    try {
        $committed[] = DB::transaction(function () use ($sequence, $i): int {
            $number = $sequence->next('concurrency-test', '2026');

            if ($i % 5 === 4) {
                throw new SimulatedBusinessFailure;
            }

            return $number;
        }, 5);
    } catch (SimulatedBusinessFailure) {
    }
}

echo json_encode($committed);
