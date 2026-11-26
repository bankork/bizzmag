<?php

namespace App\Http\Resources\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $this->employeeProfile;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'must_change_password' => $this->must_change_password,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'disabled_at' => $this->disabled_at?->toIso8601String(),
            'roles' => $this->roles->sortBy('name')->map(fn ($role) => ['id' => $role->id, 'name' => $role->name])->values()->all(),
            'employee_profile' => $profile === null ? null : [
                'id' => $profile->id,
                'display_name' => $profile->display_name,
                'job_title' => $profile->job_title,
                'is_active' => $profile->is_active,
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
