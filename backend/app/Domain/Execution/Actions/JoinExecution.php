<?php

namespace App\Domain\Execution\Actions;

use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionStatus;
use App\Domain\Execution\Models\JoinedVia;
use App\Domain\Execution\Support\SubmissionWindow;
use App\Domain\Execution\Support\WorkRecords;
use App\Domain\Identity\Exceptions\StaffNotEligible;
use App\Domain\Identity\Support\SalonStaff;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\InvalidTransition;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * An employee adds themselves to a service someone else recorded
 * (FR-ATTR-013, SRS §6.3 join). Shares are recalculated with the record's
 * method (FR-ATTR-008).
 */
final class JoinExecution
{
    public function __construct(
        private readonly WorkRecords $work,
        private readonly SubmissionWindow $window,
        private readonly SalonStaff $staff,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, Execution $execution): Execution
    {
        $orderId = WorkRecords::orderIdOf($execution);

        return DB::transaction(function () use ($actor, $execution, $orderId): Execution {
            [$order, , $execution] = $this->work->lockExecution($execution, $orderId);

            if ($execution->status !== ExecutionStatus::Confirmed) {
                throw new InvalidTransition('This record is disputed or replaced, so it cannot be joined. Ask a supervisor.');
            }

            $this->window->check($order, $actor);
            $own = $this->staff->profileFor($actor) ?? throw new StaffNotEligible('You are not set up to be credited for salon work.');

            if (in_array($own->id, $execution->contributorIds(), true)) {
                throw new InvalidTransition('You are already on this record.');
            }

            $before = $execution->shareMap();
            $this->work->resplit($execution, [...$execution->contributorIds(), $own->id], $execution->primaryEmployeeId(), JoinedVia::Join);
            $execution->load('contributors');

            $this->audit->record(new AuditEntry('execution.joined', 'execution', $execution->id,
                before: ['shares' => $before], after: ['shares' => $execution->shareMap(), 'employee_id' => $own->id],
                actorId: $actor->id, orderId: $order->id));

            return $execution;
        });
    }
}
