<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Lets a disabled user sign in again.
 */
final class EnableUser
{
    public function __construct(private readonly AuditRecorder $audit) {}

    public function __invoke(User $user): User
    {
        if ($user->is_active) {
            return $user;
        }

        return DB::transaction(function () use ($user): User {
            $user->forceFill(['is_active' => true, 'disabled_at' => null, 'disabled_by' => null])->save();

            $this->audit->record(new AuditEntry('user.enabled', 'user', $user->id, before: ['is_active' => false], after: ['is_active' => true]));

            return $user;
        });
    }
}
