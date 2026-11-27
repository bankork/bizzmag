<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Events\DisputeDismissed;
use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionStatus;
use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Identity\Support\SegregationOfDuties;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A supervisor decides a dispute was wrong and the record stands
 * (FR-EXE-009, SRS §6.3 Disputed → Confirmed). Not by the person who
 * recorded it or who disputed it (BR-040).
 */
final class DismissDispute
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

            if ($execution->status !== ExecutionStatus::Disputed) {
                throw new InvalidTransition('This record is not disputed.');
            }

            $exempt = $this->duties->check($actor, [$execution->submitted_by, $execution->disputed_by]);

            $execution->forceFill([
                'status' => ExecutionStatus::Confirmed,
                'resolution_reason' => $reason,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ])->save();

            $this->audit->record(new AuditEntry('execution.dispute_dismissed', 'execution', $execution->id,
                before: ['status' => ExecutionStatus::Disputed->value], after: ['status' => ExecutionStatus::Confirmed->value],
                reason: $reason, actorId: $actor->id, orderId: $order->id, sodExempt: $exempt));

            DisputeDismissed::dispatch($execution->id, $actor->id);

            return $execution;
        });
    }
}
