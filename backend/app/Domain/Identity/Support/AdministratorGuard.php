<?php

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Exceptions\LastAdministrator;
use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Keeps at least one active user who can manage both users and roles, so
 * the salon can never lock itself out of its own staff management.
 *
 * Call it inside the transaction, after the change: if no administrator
 * remains it throws and the change rolls back.
 */
final class AdministratorGuard
{
    public function ensureRemains(int $businessId): void
    {
        if ($this->count($businessId) === 0) {
            throw new LastAdministrator;
        }
    }

    public function count(int $businessId): int
    {
        $required = PermissionCatalogue::ADMINISTRATION;

        $administrators = DB::table('users')
            ->join('business_user', function ($join) use ($businessId): void {
                $join->on('business_user.user_id', '=', 'users.id')
                    ->where('business_user.business_id', $businessId)
                    ->where('business_user.is_active', true);
            })
            ->join('model_has_roles', function ($join) use ($businessId): void {
                $join->on('model_has_roles.model_id', '=', 'users.id')
                    ->where('model_has_roles.model_type', (new User)->getMorphClass())
                    ->where('model_has_roles.business_id', $businessId);
            })
            ->join('role_has_permissions', 'role_has_permissions.role_id', '=', 'model_has_roles.role_id')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->where('users.is_active', true)
            ->whereIn('permissions.name', $required)
            ->groupBy('users.id')
            ->havingRaw('COUNT(DISTINCT permissions.name) = ?', [count($required)])
            ->select('users.id');

        return DB::query()->fromSub($administrators, 'administrators')->count();
    }
}
