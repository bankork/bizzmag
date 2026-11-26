<?php

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Models\Role;

/**
 * The audited view of a role.
 */
final class RoleSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Role $role): array
    {
        return [
            'name' => $role->name,
            'description' => $role->description,
            'discount_cap_bp' => $role->discount_cap_bp,
            'discount_cap_kobo' => $role->discount_cap_kobo,
            'permissions' => $role->permissions()->orderBy('name')->pluck('name')->all(),
        ];
    }
}
