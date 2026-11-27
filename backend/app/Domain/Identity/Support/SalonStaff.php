<?php

namespace App\Domain\Identity\Support;

use App\Domain\Identity\Exceptions\StaffNotEligible;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * The people who can be assigned to or credited for salon work (FR-ATTR-002,
 * SRS §14.22): an active employee profile, an active user and an active
 * membership of the current business. A disabled employee stays on past
 * records but cannot be added to new ones.
 */
final class SalonStaff
{
    /**
     * @return Builder<EmployeeProfile>
     */
    public function eligibleQuery(): Builder
    {
        return EmployeeProfile::query()
            ->where('employee_profiles.is_active', true)
            ->whereHas('user', fn (Builder $user) => $user->where('is_active', true)
                ->whereHas('businesses', fn (Builder $business) => $business
                    ->whereColumn('businesses.id', 'employee_profiles.business_id')
                    ->where('business_user.is_active', true)));
    }

    /**
     * The signed-in person's own profile, when they do salon work and may be credited.
     */
    public function profileFor(User $user): ?EmployeeProfile
    {
        return $this->eligibleQuery()->where('user_id', $user->id)->first();
    }

    /**
     * Every given ID must be someone who can be credited. Returns them keyed by ID.
     *
     * @param  list<int>  $employeeIds
     * @return Collection<int, EmployeeProfile>
     */
    public function requireEligible(array $employeeIds): Collection
    {
        $unique = array_values(array_unique($employeeIds));

        if (count($unique) !== count($employeeIds)) {
            throw new StaffNotEligible('Each person can be added only once.');
        }

        $found = $this->eligibleQuery()->whereKey($unique)->get()->keyBy('id');

        if ($found->count() !== count($unique)) {
            throw new StaffNotEligible(context: ['employee_ids' => array_values(array_diff($unique, $found->keys()->all()))]);
        }

        return $found;
    }
}
