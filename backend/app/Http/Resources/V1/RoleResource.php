<?php

namespace App\Http\Resources\V1;

use App\Domain\Identity\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Role
 */
class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'system_key' => $this->system_key,
            'is_system' => $this->isSystem(),
            'is_owner' => $this->isOwner(),
            'discount_cap_bp' => $this->discount_cap_bp,
            'discount_cap_kobo' => $this->discount_cap_kobo,
            'permissions' => $this->permissions->pluck('name')->sort()->values()->all(),
            'users_count' => (int) ($this->users_count ?? 0),
        ];
    }
}
