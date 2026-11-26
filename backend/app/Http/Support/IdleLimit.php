<?php

namespace App\Http\Support;

use App\Domain\Business\Settings\BusinessSettings;
use App\Domain\Identity\Auth\IdlePolicy;
use App\Models\User;

/**
 * The idle limit in seconds for a user in the current business: the
 * policy (SEC-018) applied to the limits saved in Settings.
 */
final class IdleLimit
{
    public function __construct(
        private readonly IdlePolicy $policy,
        private readonly BusinessSettings $settings,
    ) {}

    public function for(User $user): int
    {
        return $this->policy->limitSeconds(
            $user,
            $this->settings->integer('sessions.idle_minutes_privileged'),
            $this->settings->integer('sessions.idle_days_low_risk'),
        );
    }
}
