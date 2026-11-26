<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\SelfActionNotAllowed;
use App\Domain\Identity\Support\SessionTerminator;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sets a temporary password for someone who forgot theirs (P1 Q2). They are
 * signed out and must choose a new password at their next sign-in.
 */
final class ResetPassword
{
    public function __construct(
        private readonly SessionTerminator $sessions,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(?User $actor, User $user, string $temporaryPassword): void
    {
        if ($actor !== null && $actor->is($user)) {
            throw new SelfActionNotAllowed;
        }

        DB::transaction(function () use ($user, $temporaryPassword): void {
            $user->forceFill([
                'password' => $temporaryPassword,
                'must_change_password' => true,
                'password_changed_at' => now(),
            ])->save();

            $this->sessions->endAll($user->id);

            $this->audit->record(new AuditEntry('auth.password_reset', 'user', $user->id));
        });
    }
}
