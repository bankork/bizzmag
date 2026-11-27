<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Events\ContributorsChanged;
use App\Domain\Execution\Exceptions\InvalidAllocation;
use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionStatus;
use App\Domain\Execution\Models\JoinedVia;
use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Identity\Support\SalonStaff;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\AccessDenied;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The person who recorded a service changes who worked on it, while the
 * visit is open (FR-ATTR-013, SRS §6.3). They cannot remove themselves or
 * change the primary; a supervisor can (allocation.override). Added and
 * removed people are notified.
 */
final class EditContributors
{
    public const SUBMITTER_ONLY = 'execution.submitter';

    public function __construct(
        private readonly WorkRecords $work,
        private readonly SalonStaff $staff,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  list<int>  $employeeIds  Everyone who should be on the record.
     */
    public function __invoke(User $actor, Execution $execution, array $employeeIds): Execution
    {
        $orderId = WorkRecords::orderIdOf($execution);

        return DB::transaction(function () use ($actor, $execution, $employeeIds, $orderId): Execution {
            [$order, , $execution] = $this->work->lockExecution($execution, $orderId);

            if ($execution->submitted_by !== $actor->id) {
                throw AccessDenied::missing(self::SUBMITTER_ONLY);
            }

            if (! $order->isOpen()) {
                throw new OrderNotOpen;
            }

            if ($execution->status !== ExecutionStatus::Confirmed) {
                throw new InvalidTransition('This record is disputed or replaced. Ask a supervisor to change it.');
            }

            if (count(array_unique($employeeIds)) !== count($employeeIds)) {
                throw new InvalidAllocation('Each person can be added only once.');
            }

            $current = $execution->contributorIds();
            $primaryId = $execution->primaryEmployeeId();
            $own = $this->staff->profileFor($actor);

            if (! in_array($primaryId, $employeeIds, true)) {
                throw new InvalidAllocation('The main person stays on the record. A supervisor can change who that is.');
            }

            if ($own !== null && in_array($own->id, $current, true) && ! in_array($own->id, $employeeIds, true)) {
                throw new InvalidAllocation('You cannot remove yourself from work you recorded. Ask a supervisor.');
            }

            $added = array_values(array_diff($employeeIds, $current));
            $removed = array_values(array_diff($current, $employeeIds));

            if ($added === [] && $removed === []) {
                return $execution;
            }

            $this->staff->requireEligible($added);

            $before = $execution->shareMap();
            $ordered = [...array_values(array_intersect($current, $employeeIds)), ...$added];
            $this->work->resplit($execution, $ordered, $primaryId, JoinedVia::Edit);
            $execution->load('contributors');

            $this->audit->record(new AuditEntry('execution.contributors_changed', 'execution', $execution->id,
                before: ['shares' => $before], after: ['shares' => $execution->shareMap(), 'added' => $added, 'removed' => $removed],
                actorId: $actor->id, orderId: $order->id));

            ContributorsChanged::dispatch($execution->id, $added, $removed, $actor->id);

            return $execution;
        });
    }
}
