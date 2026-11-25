<?php

/**
 * Runs the same idempotent "payment" with the same key as every other
 * worker. The operation records an audit event as its side effect and is
 * slowed down to widen the race window.
 *
 * Prints the result body and whether it was a replay.
 */

use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Idempotency\IdempotencyGuard;
use App\Domain\Shared\Idempotency\IdempotentResult;

$app = require __DIR__.'/bootstrap.php';

$guard = $app->make(IdempotencyGuard::class);
$audit = $app->make(AuditRecorder::class);
$worker = (string) ($argv[1] ?? '0');

waitForStart();

$result = $guard->run('user:7', 'concurrent-payment-0001', ['amount_kobo' => 1000000], function () use ($audit, $worker): IdempotentResult {
    $audit->record(new AuditEntry(action: 'payment.recorded', after: ['worker' => $worker]));
    usleep(300000);

    return IdempotentResult::fresh(201, ['recorded_by_worker' => $worker]);
});

echo json_encode(['body' => $result->body, 'replayed' => $result->replayed]);
