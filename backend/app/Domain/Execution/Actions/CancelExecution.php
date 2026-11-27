<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Events\ExecutionCancelled;
use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionStatus;
use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Identity\Support\SegregationOfDuties;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A supervisor cancels a record, for example work recorded on the wrong
 * service (SRS §6.3 → Cancelled). The record stays; the service goes back to
 * waiting for its work to be recorded. Not by the person who recorded or
 * disputed it (BR-040).
 */
final class CancelExecution
{
    public function __construct(
        private readonly WorkRecords $work,
        private readonly SegregationOfDuties $duties,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, Execution $execution, string $reason): Execution
    {
        $orderId = WorkRecords::orderIdOf($execution);

        return DB::transaction(function () use ($actor, $execution, $reason, $orderId): Execution {
            [$order, , $execution] = $this->work->lockExecution($execution, $orderId);

            if ($order->status === OrderStatus::Cancelled) {
                throw new OrderNotOpen;
            }

            if (! $execution->isActive()) {
                throw new InvalidTransition('This record has already been replaced or cancelled.');
            }

            $exempt = $this->duties->check($actor, [$execution->submitted_by, $execution->disputed_by]);
            $this->cancel($execution, $actor, $reason);

            $this->audit->record(new AuditEntry('execution.cancelled', 'execution', $execution->id,
                after: ['status' => ExecutionStatus::Cancelled->value], reason: $reason,
                actorId: $actor->id, orderId: $order->id, sodExempt: $exempt));

            return $execution;
        });
    }

    /**
     * Mark a locked, active record cancelled and notify its people. Also
     * used when its item or its whole visit is cancelled.
     */
    public function cancel(Execution $execution, User $actor, string $reason): void
    {
        $execution->forceFill([
            'status' => ExecutionStatus::Cancelled,
            'resolution_reason' => $reason,
            'resolved_by' => $actor->id,
            'resolved_at' => now(),
        ])->save();

        ExecutionCancelled::dispatch($execution->id, $actor->id);
    }
}
