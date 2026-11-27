<?php

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Exceptions\SegregationOfDutiesViolation;
use App\Domain\Identity\Models\Role;
use App\Models\User;

/**
 * BR-040: the person approving, correcting or resolving must not be the
 * person who asked for it or who recorded the work. The Owner role is
 * exempt, and every exempt action is flagged in the audit log.
 */
final class SegregationOfDuties
{
    /**
     * @param  list<int|null>  $involvedUserIds  The requester, submitter or disputer.
     * @return bool Whether the action goes ahead under the owner's exemption.
     */
    public function check(User $actor, array $involvedUserIds): bool
    {
        if (! in_array($actor->id, $involvedUserIds, true)) {
            return false;
        }

        if ($actor->roles->contains(fn (Role $role) => $role->isOwner())) {
            return true;
        }

        throw new SegregationOfDutiesViolation;
    }
}
