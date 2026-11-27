<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Allocation\AllocationMethod;
use App\Domain\Execution\Exceptions\InvalidAllocation;
use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionStatus;
use App\Domain\Execution\Models\JoinedVia;
use App\Domain\Execution\Support\ShareCalculator;
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
 * A supervisor changes how one record's credit is split: the method, the
 * primary, the primary's share, or custom percentages totalling exactly
 * 100% (FR-ATTR-003, 005, 009). A reason is required, and the person who
 * recorded the work cannot override it themselves (BR-040).
 */
final class OverrideAllocation
{
    public function __construct(
        private readonly WorkRecords $work,
        private readonly ShareCalculator $calculator,
        private readonly SegregationOfDuties $duties,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array<int, int>|null  $customShares  Employee ID => basis points, for the Custom method.
     */
    public function __invoke(
        User $actor,
        Execution $execution,
        AllocationMethod $method,
        string $reason,
        ?int $primaryEmployeeId = null,
        ?int $primaryShareBp = null,
        ?array $customShares = null,
    ): Execution {
        $orderId = WorkRecords::orderIdOf($execution);

        return DB::transaction(function () use ($actor, $execution, $method, $reason, $primaryEmployeeId, $primaryShareBp, $customShares, $orderId): Execution {
            [$order, $item, $execution] = $this->work->lockExecution($execution, $orderId);

            if ($order->status === OrderStatus::Cancelled) {
                throw new OrderNotOpen;
            }

            if ($execution->status !== ExecutionStatus::Confirmed) {
                throw new InvalidTransition('Resolve the dispute first, by dismissing it or correcting the record.');
            }

            $exempt = $this->duties->check($actor, [$execution->submitted_by]);
            $employeeIds = $execution->contributorIds();
            $primaryId = $primaryEmployeeId ?? $execution->primaryEmployeeId();

            if (! in_array($primaryId, $employeeIds, true)) {
                throw new InvalidAllocation('The main person must be someone on the record.');
            }

            if ($method === AllocationMethod::Custom) {
                $given = array_map('intval', array_keys($customShares ?? []));
                sort($given);
                $expected = $employeeIds;
                sort($expected);

                if ($given !== $expected) {
                    throw new InvalidAllocation('Give a percentage for everyone on the record, and only for them.');
                }
            }

            $share = $method === AllocationMethod::PrimaryAssistant
                ? ($primaryShareBp ?? $execution->primary_share_bp ?? $item->primary_share_bp_snapshot)
                : null;

            $before = ['method' => $execution->allocation_method->value, 'primary_employee_id' => $execution->primaryEmployeeId(), 'shares' => $execution->shareMap()];
            $shares = $this->calculator->shares($method, $share, $employeeIds, $primaryId, $customShares);

            $this->work->write($execution, $shares, $primaryId, JoinedVia::Override);
            $execution->forceFill([
                'allocation_method' => $method,
                'primary_share_bp' => $share,
                'override_reason' => $reason,
                'overridden_by' => $actor->id,
                'overridden_at' => now(),
            ])->save();

            $this->audit->record(new AuditEntry('allocation.overridden', 'execution', $execution->id,
                before: $before, after: ['method' => $method->value, 'primary_employee_id' => $primaryId, 'shares' => $shares],
                reason: $reason, actorId: $actor->id, orderId: $order->id, sodExempt: $exempt));

            return $execution->load('contributors');
        });
    }
}
