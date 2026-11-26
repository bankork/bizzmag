<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\SelfActionNotAllowed;
use App\Domain\Identity\Support\AdministratorGuard;
use App\Domain\Identity\Support\SessionTerminator;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Disables a user (FR-AUTH-005, §14.22). They are signed out everywhere at
 * once and cannot sign in. Nothing they did is changed or hidden.
 */
final class DisableUser
{
    public function __construct(
        private readonly CurrentBusiness $business,
        private readonly AdministratorGuard $administrators,
        private readonly SessionTerminator $sessions,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, User $user, ?string $reason): User
    {
        if ($actor->is($user)) {
            throw new SelfActionNotAllowed;
        }

        if (! $user->is_active) {
            return $user;
        }

        return DB::transaction(function () use ($actor, $user, $reason): User {
            $user->forceFill(['is_active' => false, 'disabled_at' => now(), 'disabled_by' => $actor->id])->save();

            $this->administrators->ensureRemains($this->business->require());
            $this->sessions->endAll($user->id);

            $this->audit->record(new AuditEntry('user.disabled', 'user', $user->id, before: ['is_active' => true], after: ['is_active' => false], reason: $reason));

            return $user;
        });
    }
}
