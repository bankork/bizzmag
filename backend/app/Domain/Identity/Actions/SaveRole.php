<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Exceptions\SystemRoleProtected;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Identity\Support\AdministratorGuard;
use App\Domain\Identity\Support\RoleSnapshot;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates or edits a role: name, description, discount caps and
 * permissions (FR-IAM-003, FR-IAM-009). Every change is audited with before
 * and after values (FR-IAM-006).
 *
 * The Owner role keeps exactly its SRS §3.3 permissions (everything except
 * the employee-only actions), and no change may leave the business without
 * an active administrator.
 */
final class SaveRole
{
    public function __construct(
        private readonly CurrentBusiness $business,
        private readonly AdministratorGuard $administrators,
        private readonly PermissionRegistrar $registrar,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array{name?: string, description?: ?string, discount_cap_bp?: ?int, discount_cap_kobo?: ?int}  $attributes
     * @param  list<string>|null  $permissions  Null leaves permissions unchanged.
     */
    public function __invoke(?Role $role, array $attributes, ?array $permissions): Role
    {
        $businessId = $this->business->require();

        if ($permissions !== null) {
            $unknown = array_diff($permissions, PermissionCatalogue::names());

            if ($unknown !== []) {
                throw new InvalidArgumentException('Unknown permissions: '.implode(', ', $unknown));
            }

            $ownerPermissions = PermissionCatalogue::defaultsFor(SystemRole::Owner);

            if ($role?->isOwner() && (array_diff($ownerPermissions, $permissions) !== [] || array_diff($permissions, $ownerPermissions) !== [])) {
                throw new SystemRoleProtected('The Owner role\'s permissions cannot be changed.');
            }
        }

        return DB::transaction(function () use ($role, $attributes, $permissions, $businessId): Role {
            $before = $role === null ? null : RoleSnapshot::of($role);

            if ($role === null) {
                $role = Role::query()->create([...$attributes, 'business_id' => $businessId, 'guard_name' => 'web']);
            } else {
                $role->fill($attributes)->save();
            }

            if ($permissions !== null) {
                $role->syncPermissions($permissions);
            }

            $this->registrar->forgetCachedPermissions();
            $this->administrators->ensureRemains($businessId);

            $after = RoleSnapshot::of($role);

            if ($before !== $after) {
                $this->audit->record(new AuditEntry(
                    $before === null ? 'role.created' : 'role.updated',
                    'role',
                    (int) $role->getKey(),
                    before: $before,
                    after: $after,
                ));
            }

            return $role;
        });
    }
}
