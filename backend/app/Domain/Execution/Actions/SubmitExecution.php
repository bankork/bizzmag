<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Events\ContributorsChanged;
use App\Domain\Execution\Exceptions\ExecutionExists;
use App\Domain\Execution\Exceptions\InvalidAllocation;
use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionStatus;
use App\Domain\Execution\Models\JoinedVia;
use App\Domain\Execution\Support\ShareCalculator;
use App\Domain\Execution\Support\SubmissionWindow;
use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Identity\Support\SalonStaff;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Records that a service was done (FR-EXE-002 to 008, FR-ATTR-001 to 008,
 * SRS §6.3 → Confirmed). Confirmed at once (BR-004).
 *
 * The person recording is the primary when they do salon work. Someone who
 * does not (the owner, or a supervisor recording for a stylist whose phone
 * is flat) chooses the primary and is not credited (P3 Q1).
 *
 * If the item already has an active record, the second person is told so
 * and offered "join" (FR-EXE-007). The visit lock makes simultaneous
 * submissions run one after the other; the unique active_item_key is the
 * database's backstop.
 */
final class SubmitExecution
{
    public function __construct(
        private readonly WorkRecords $work,
        private readonly SubmissionWindow $window,
        private readonly SalonStaff $staff,
        private readonly ShareCalculator $calculator,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  list<int>  $colleagueIds  Other people who worked on it.
     * @param  int|null  $primaryEmployeeId  Required when the person recording does no salon work.
     */
    public function __invoke(User $actor, OrderItem $item, array $colleagueIds = [], ?int $primaryEmployeeId = null): Execution
    {
        return DB::transaction(function () use ($actor, $item, $colleagueIds, $primaryEmployeeId): Execution {
            [$order, $item] = $this->work->lockItem($item);

            if (! $item->isService()) {
                throw new InvalidTransition('Only services are recorded as work. Products need no record.');
            }

            if (! $item->status->acceptsWork()) {
                throw new InvalidTransition('This service is no longer on the visit.');
            }

            $late = $this->window->check($order, $actor);
            $own = $this->staff->profileFor($actor);

            $existing = $this->work->activeFor($item);

            if ($existing !== null) {
                throw $this->exists($existing, $actor, $own?->id);
            }

            $primaryId = $this->primary($own?->id, $primaryEmployeeId);
            if (count(array_unique($colleagueIds)) !== count($colleagueIds)) {
                throw new InvalidAllocation('Each person can be added only once.');
            }

            $employeeIds = array_values(array_unique([$primaryId, ...$colleagueIds]));

            $this->staff->requireEligible($employeeIds);

            $method = ShareCalculator::defaultMethod($item);
            $shares = $this->calculator->shares($method, $item->primary_share_bp_snapshot, $employeeIds, $primaryId);

            try {
                $execution = Execution::query()->create([
                    'order_item_id' => $item->id,
                    'status' => ExecutionStatus::Confirmed,
                    'submitted_by' => $actor->id,
                    'submitted_at' => now(),
                    'late' => $late,
                    'allocation_method' => $method,
                    'primary_share_bp' => $item->primary_share_bp_snapshot,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new ExecutionExists(context: ['can_join' => false]);
            }

            $this->work->write($execution, $shares, $primaryId, JoinedVia::Submit);

            $this->audit->record(new AuditEntry('execution.submitted', 'execution', $execution->id, after: [
                'order_item_id' => $item->id,
                'method' => $method->value,
                'primary_employee_id' => $primaryId,
                'shares' => $shares,
                'late' => $late,
            ], actorId: $actor->id, orderId: $order->id));

            $named = array_values(array_diff($employeeIds, $own === null ? [] : [$own->id]));

            if ($named !== []) {
                ContributorsChanged::dispatch($execution->id, $named, [], $actor->id);
            }

            return $execution;
        });
    }

    private function primary(?int $ownId, ?int $requested): int
    {
        if ($ownId !== null) {
            if ($requested !== null && $requested !== $ownId) {
                throw new InvalidAllocation('You are recorded as the main person on work you record yourself. A supervisor can change this afterwards.');
            }

            return $ownId;
        }

        return $requested ?? throw new InvalidAllocation('Choose who did the work, because you are not set up to be credited for salon work.');
    }

    private function exists(Execution $existing, User $actor, ?int $ownId): ExecutionExists
    {
        $existing->load('contributors');
        $canJoin = $ownId !== null
            && $existing->status === ExecutionStatus::Confirmed
            && ! in_array($ownId, $existing->contributorIds(), true)
            && $actor->checkPermissionTo('execution.join');

        return new ExecutionExists(context: ['execution_id' => $existing->id, 'can_join' => $canJoin]);
    }
}
