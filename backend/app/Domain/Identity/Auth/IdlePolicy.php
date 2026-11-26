<?php

namespace App\Domain\Identity\Auth;

use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Models\User;

/**
 * Idle limits by role (SEC-018).
 *
 * A user whose permissions are all low risk (the Employee defaults) may stay
 * signed in on a personal device for days. Anyone with more, for example
 * any financial permission, gets the short limit.
 */
final class IdlePolicy
{
    public function isLowRisk(User $user): bool
    {
        $permissions = $user->getAllPermissions()->pluck('name')->all();

        return array_diff($permissions, PermissionCatalogue::LOW_RISK) === [];
    }

    public function limitSeconds(User $user, int $privilegedMinutes, int $lowRiskDays): int
    {
        return $this->isLowRisk($user) ? $lowRiskDays * 86_400 : $privilegedMinutes * 60;
    }
}
