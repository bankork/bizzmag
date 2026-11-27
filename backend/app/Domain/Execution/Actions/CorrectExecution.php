<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Allocation\AllocationMethod;
use App\Domain\Execution\Events\ExecutionCorrected;
use App\Domain\Execution\Exceptions\InvalidAllocation;
use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionStatus;
use App\Domain\Execution\Models\JoinedVia;
use App\Domain\Execution\Support\ShareCalculator;
use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Identity\Support\SalonStaff;
use App\Domain\Identity\Support\SegregationOfDuties;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * A supervisor replaces a record with a corrected one (FR-EXE-010, BR-006,
 * SRS §6.3 → Superseded + new Confirmed). The original stays, marked
 * replaced and linked from the new one. People, primary and split may
 * change. Not by the person who recorded or disputed it (BR-040).
 */
final class CorrectExecution
{
    public function __construct(
        private readonly WorkRecords $work,
        private readonly SalonStaff $staff,
        private readonly ShareCalculator $calculator,
        private readonly SegregationOfDuties $duties,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  list<int>  $employeeIds  Everyone on the corrected record, including the primary.
     * @param  array<int, int>|null  $customShares  Employee ID => basis points, for the Custom method.
     */
    public function __invoke(
        User $actor,
        Execution $execution,
        array $employeeIds,
        int $primaryEmployeeId,
        string $reason,
        ?AllocationMethod $method = null,
        ?int $primaryShareBp = null,
        ?array $customShares = null,
    ): Execution {
        $orderId = WorkRecords::orderIdOf($execution);

        return DB::transaction(function () use ($actor, $execution, $employeeIds, $primaryEmployeeId, $reason, $method, $primaryShareBp, $customShares, $orderId): Execution {
            [$order, $item, $original] = $this->work->lockExecution($execution, $orderId);

            if ($order->status === OrderStatus::Cancelled) {
                throw new OrderNotOpen;
            }

            if (! $original->isActive()) {
                throw new InvalidTransition('This record has already been replaced or cancelled.');
            }

            $exempt = $this->duties->check($actor, [$original->submitted_by, $original->disputed_by]);

            if (count(array_unique($employeeIds)) !== count($employeeIds) || ! in_array($primaryEmployeeId, $employeeIds, true)) {
                throw new InvalidAllocation('List each person once, and include the main person.');
            }

            $this->staff->requireEligible(array_values(array_diff($employeeIds, $original->contributorIds())));

            $method ??= ShareCalculator::defaultMethod($item);
            $share = $method === AllocationMethod::PrimaryAssistant ? ($primaryShareBp ?? $item->primary_share_bp_snapshot) : null;
            $shares = $this->calculator->shares($method, $share, $employeeIds, $primaryEmployeeId, $customShares);
            $before = ['status' => $original->status->value, 'method' => $original->allocation_method->value, 'shares' => $original->shareMap()];

            $original->forceFill([
                'status' => ExecutionStatus::Superseded,
                'resolution_reason' => $reason,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ])->save();

            $replacement = Execution::query()->create([
                'order_item_id' => $item->id,
                'status' => ExecutionStatus::Confirmed,
                'submitted_by' => $actor->id,
                'submitted_at' => now(),
                'late' => $original->late,
                'supersedes_id' => $original->id,
                'allocation_method' => $method,
                'primary_share_bp' => $share,
            ]);

            $this->work->write($replacement, $shares, $primaryEmployeeId, JoinedVia::Correction);

            $this->audit->record(new AuditEntry('execution.corrected', 'execution', $original->id,
                before: $before,
                after: ['status' => ExecutionStatus::Superseded->value, 'replacement_id' => $replacement->id, 'method' => $method->value, 'primary_employee_id' => $primaryEmployeeId, 'shares' => $shares],
                reason: $reason, actorId: $actor->id, orderId: $order->id, sodExempt: $exempt));

            ExecutionCorrected::dispatch($original->id, $replacement->id, $actor->id);

            return $replacement->load('contributors');
        });
    }
}
