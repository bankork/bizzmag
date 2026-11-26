<?php

namespace App\Http\Presenters;

use App\Domain\Business\Models\Business;
use App\Domain\Identity\Auth\IdlePolicy;
use App\Domain\Identity\Support\DiscountCap;
use App\Http\Support\IdleLimit;
use App\Models\User;

/**
 * The session bootstrap for the web app (Stage 3 §6.3): who you are, which
 * business, what you may do and how long an idle session lasts.
 *
 * The web app uses the permissions only to show or hide things; the backend
 * checks every request.
 */
final class MePresenter
{
    public function __construct(
        private readonly IdlePolicy $idle,
        private readonly IdleLimit $idleLimit,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(User $user, Business $business): array
    {
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $permissions = $user->getAllPermissions()->pluck('name')->sort()->values()->all();
        $profile = $user->employeeProfile()->first();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'must_change_password' => $user->must_change_password,
                'employee_profile' => $profile === null ? null : [
                    'id' => $profile->id,
                    'display_name' => $profile->display_name,
                    'job_title' => $profile->job_title,
                    'is_active' => $profile->is_active,
                ],
            ],
            'business' => BusinessPresenter::summary($business),
            'roles' => $user->roles()->orderBy('name')->get(['roles.id', 'roles.name', 'roles.system_key'])
                ->map(fn ($role) => ['id' => $role->id, 'name' => $role->name, 'system_key' => $role->system_key])
                ->values()->all(),
            'permissions' => $permissions,
            'discount_cap' => DiscountCap::for($user),
            'session' => [
                'kind' => $this->idle->isLowRisk($user) ? 'personal' : 'privileged',
                'idle_timeout_seconds' => $this->idleLimit->for($user),
            ],
            'home' => self::homeArea($permissions),
        ];
    }

    /**
     * Where the user starts in the web app, chosen by permission.
     *
     * @param  list<string>  $permissions
     */
    public static function homeArea(array $permissions): string
    {
        $admin = ['user.manage', 'role.manage', 'settings.manage', 'dashboard.view', 'catalogue.manage'];

        return match (true) {
            array_intersect($admin, $permissions) !== [] => 'admin',
            in_array('order.create', $permissions, true) => 'desk',
            default => 'staff',
        };
    }
}
