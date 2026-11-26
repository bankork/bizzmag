<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Support\AdministratorGuard;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Sets a user's roles (FR-AUTH-004, FR-IAM-008). A user may hold several
 * roles; their permissions are the union.
 */
final class AssignRoles
{
    public function __construct(
        private readonly CurrentBusiness $business,
        private readonly AdministratorGuard $administrators,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  list<int>  $roleIds
     */
    public function __invoke(User $user, array $roleIds): User
    {
        $businessId = $this->business->require();

        return DB::transaction(function () use ($user, $roleIds, $businessId): User {
            $before = $user->roles()->orderBy('name')->pluck('name')->all();

            $user->syncRoles(Role::query()->forBusiness($businessId)->whereKey($roleIds)->get());
            $user->unsetRelation('roles')->unsetRelation('permissions');

            $this->administrators->ensureRemains($businessId);

            $after = $user->roles()->orderBy('name')->pluck('name')->all();

            if ($before !== $after) {
                $this->audit->record(new AuditEntry('user.roles_changed', 'user', $user->id, before: ['roles' => $before], after: ['roles' => $after]));
            }

            return $user;
        });
    }
}
