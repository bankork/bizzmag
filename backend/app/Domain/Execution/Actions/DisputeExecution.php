<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Events\ExecutionDisputed;
use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionStatus;
use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Identity\Support\SalonStaff;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\AccessDenied;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Someone named on a record, or a supervisor, disagrees with it
 * (FR-EXE-009, SRS §6.3 → Disputed). It stays the item's active record,
 * blocks ordinary finalisation from P4 (R2), and its people and the
 * supervisors are notified.
 */
final class DisputeExecution
{
    /** Marker for a refusal because the person is not named on the record. */
    public const NAMED_ONLY = 'execution.dispute (named on the record)';

    public function __construct(
        private readonly WorkRecords $work,
        private readonly SalonStaff $staff,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, Execution $execution, string $reason): Execution
    {
        $orderId = WorkRecords::orderIdOf($execution);

        return DB::transaction(function () use ($actor, $execution, $reason, $orderId): Execution {
            [$order, , $execution] = $this->work->lockExecution($execution, $orderId);

            if (! $actor->checkPermissionTo('execution.correct')) {
                $own = $this->staff->profileFor($actor);

                if ($own === null || ! in_array($own->id, $execution->contributorIds(), true)) {
                    throw AccessDenied::missing(self::NAMED_ONLY);
                }
            }

            if ($order->status === OrderStatus::Cancelled) {
                throw new OrderNotOpen;
            }

            if ($execution->status !== ExecutionStatus::Confirmed) {
                throw new InvalidTransition('Only a confirmed record can be disputed.');
            }

            $execution->forceFill([
                'status' => ExecutionStatus::Disputed,
                'dispute_reason' => $reason,
                'disputed_by' => $actor->id,
                'disputed_at' => now(),
            ])->save();

            $this->audit->record(new AuditEntry('execution.disputed', 'execution', $execution->id,
                before: ['status' => ExecutionStatus::Confirmed->value], after: ['status' => ExecutionStatus::Disputed->value],
                reason: $reason, actorId: $actor->id, orderId: $order->id));

            ExecutionDisputed::dispatch($execution->id, $actor->id);

            return $execution;
        });
    }
}
