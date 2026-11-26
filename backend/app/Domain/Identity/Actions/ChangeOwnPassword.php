<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\IncorrectPassword;
use App\Domain\Identity\Support\SessionTerminator;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * A user changes their own password (FR-AUTH-006). Their other sessions are
 * signed out; the current one stays.
 */
final class ChangeOwnPassword
{
    public function __construct(
        private readonly SessionTerminator $sessions,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $user, string $currentPassword, string $newPassword, ?string $currentSessionId): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new IncorrectPassword;
        }

        DB::transaction(function () use ($user, $newPassword, $currentSessionId): void {
            $user->forceFill([
                'password' => $newPassword,
                'must_change_password' => false,
                'password_changed_at' => now(),
            ])->save();

            $this->sessions->endAll($user->id, $currentSessionId);

            $this->audit->record(new AuditEntry('auth.password_changed', 'user', $user->id));
        });
    }
}
