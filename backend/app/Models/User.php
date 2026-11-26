<?php

namespace App\Models;

use App\Domain\Business\Models\Business;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Models\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * A person who signs in. Users are disabled, never deleted (FR-AUTH-005).
 *
 * @property int $id
 * @property string $name
 * @property ?string $email
 * @property ?string $phone
 * @property string $password
 * @property bool $is_active
 * @property bool $must_change_password
 * @property ?Carbon $password_changed_at
 * @property ?Carbon $last_login_at
 * @property ?Carbon $disabled_at
 * @property ?int $disabled_by
 * @property-read ?EmployeeProfile $employeeProfile
 */
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles {
        roles as private permissionRoles;
    }

    /**
     * Only these are ever mass-assigned; every write goes through an Action.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name', 'email', 'phone', 'password', 'is_active', 'must_change_password',
        'password_changed_at', 'last_login_at', 'disabled_at', 'disabled_by', 'email_verified_at',
    ];

    protected string $guard_name = 'web';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    /**
     * The user's roles in the current business (spatie/laravel-permission,
     * teams keyed by business).
     *
     * @return BelongsToMany<Role, $this>
     */
    public function roles(): BelongsToMany
    {
        return $this->permissionRoles();
    }

    /**
     * @return BelongsToMany<Business, $this>
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class)->withPivot('is_active')->withTimestamps();
    }

    /**
     * @return HasOne<EmployeeProfile, $this>
     */
    public function employeeProfile(): HasOne
    {
        return $this->hasOne(EmployeeProfile::class);
    }

    /**
     * Users who are members of the given business.
     *
     * @param  Builder<User>  $query
     */
    public function scopeInBusiness(Builder $query, int $businessId): void
    {
        $query->whereHas('businesses', fn (Builder $business) => $business->whereKey($businessId));
    }
}
