<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Identity\Permissions\SystemRole;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Makes sure every permission in the catalogue exists and the business has
 * the four default roles (SRS §3.1, §3.3).
 *
 * Safe to run again, for example after an upgrade adds permissions: a role
 * that already exists keeps the owner's edits, except the Owner role, which
 * is reset to its SRS §3.3 permissions, including any added by an upgrade.
 */
final class ProvisionDefaultRoles
{
    public function __construct(private readonly PermissionRegistrar $registrar) {}

    /**
     * @return array<string, Role> Roles keyed by system key.
     */
    public function __invoke(int $businessId): array
    {
        foreach (PermissionCatalogue::names() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $previousTeam = $this->registrar->getPermissionsTeamId();
        $this->registrar->setPermissionsTeamId($businessId);

        try {
            $roles = [];

            foreach (SystemRole::cases() as $systemRole) {
                $role = Role::query()->forBusiness($businessId)->where('system_key', $systemRole->value)->first();

                if ($role === null) {
                    $role = Role::query()->create([
                        'business_id' => $businessId,
                        'name' => $systemRole->defaultName(),
                        'guard_name' => 'web',
                        'system_key' => $systemRole->value,
                        'description' => $systemRole->description(),
                        'discount_cap_bp' => $systemRole->defaultCapBasisPoints(),
                        'discount_cap_kobo' => $systemRole->defaultCapKobo(),
                    ]);
                    $role->syncPermissions(PermissionCatalogue::defaultsFor($systemRole));
                } elseif ($systemRole === SystemRole::Owner) {
                    $role->syncPermissions(PermissionCatalogue::defaultsFor(SystemRole::Owner));
                }

                $roles[$systemRole->value] = $role;
            }

            $this->registrar->forgetCachedPermissions();

            return $roles;
        } finally {
            $this->registrar->setPermissionsTeamId($previousTeam);
        }
    }
}
