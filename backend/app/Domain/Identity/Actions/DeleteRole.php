<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\RoleInUse;
use App\Domain\Identity\Exceptions\SystemRoleProtected;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Support\RoleSnapshot;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Deletes a custom role that nobody holds. Default roles cannot be deleted.
 */
final class DeleteRole
{
    public function __construct(
        private readonly PermissionRegistrar $registrar,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(Role $role): void
    {
        if ($role->isSystem()) {
            throw new SystemRoleProtected('Default roles cannot be deleted.');
        }

        if (DB::table('model_has_roles')->where('role_id', $role->getKey())->exists()) {
            throw new RoleInUse;
        }

        DB::transaction(function () use ($role): void {
            $before = RoleSnapshot::of($role);
            $id = (int) $role->getKey();

            $role->delete();
            $this->registrar->forgetCachedPermissions();

            $this->audit->record(new AuditEntry('role.deleted', 'role', $id, before: $before));
        });
    }
}
