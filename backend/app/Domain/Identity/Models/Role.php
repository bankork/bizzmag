<?php

namespace App\Domain\Identity\Models;

use App\Domain\Identity\Permissions\SystemRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * A role in one business, with its discount caps (FR-IAM-009).
 *
 * Roles are scoped explicitly with forBusiness() rather than with a global
 * scope, because spatie/laravel-permission loads and caches roles itself.
 *
 * @property int $business_id
 * @property ?string $system_key
 * @property ?string $description
 * @property ?int $discount_cap_bp
 * @property ?int $discount_cap_kobo
 */
class Role extends SpatieRole
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'business_id' => 'integer',
            'discount_cap_bp' => 'integer',
            'discount_cap_kobo' => 'integer',
        ];
    }

    /**
     * @param  Builder<Role>  $query
     */
    public function scopeForBusiness(Builder $query, int $businessId): void
    {
        $query->where($this->qualifyColumn('business_id'), $businessId);
    }

    /**
     * The people who hold this role.
     *
     * @return MorphToMany<User, $this>
     */
    public function users(): MorphToMany
    {
        return $this->morphedByMany(User::class, 'model', 'model_has_roles', 'role_id', 'model_id');
    }

    public function systemRole(): ?SystemRole
    {
        return $this->system_key === null ? null : SystemRole::tryFrom($this->system_key);
    }

    public function isSystem(): bool
    {
        return $this->systemRole() !== null;
    }

    public function isOwner(): bool
    {
        return $this->systemRole() === SystemRole::Owner;
    }
}
