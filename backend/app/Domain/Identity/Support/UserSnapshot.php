<?php

namespace App\Domain\Identity\Support;

use App\Models\User;

/**
 * The audited view of a user: never the password or tokens.
 */
final class UserSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function of(User $user): array
    {
        $user->loadMissing('employeeProfile');
        $profile = $user->employeeProfile;

        return [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'roles' => $user->roles()->orderBy('name')->pluck('name')->all(),
            'employee_profile' => $profile === null ? null : [
                'display_name' => $profile->display_name,
                'job_title' => $profile->job_title,
                'is_active' => $profile->is_active,
            ],
        ];
    }
}
