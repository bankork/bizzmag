<?php

namespace App\Domain\Notifications\Support;

use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who receives a notification (FR-NOT-002): active members of the current
 * business, chosen by permission or by being an employee on the work.
 */
final class Recipients
{
    public function __construct(private readonly CurrentBusiness $business) {}

    /**
     * @return list<int> User IDs.
     */
    public function withPermission(string $permission): array
    {
        return $this->activeMembers()->get()
            ->filter(fn (User $user) => $user->checkPermissionTo($permission))
            ->map(fn (User $user) => $user->id)->values()->all();
    }

    /**
     * @param  list<int>  $employeeIds
     * @return list<int> User IDs.
     */
    public function employees(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $userIds = EmployeeProfile::query()->whereKey($employeeIds)->pluck('user_id')->all();

        return $this->activeMembers()->whereKey($userIds)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * @param  list<int>  $userIds
     * @return list<int>
     */
    public function users(array $userIds): array
    {
        return $userIds === [] ? [] : $this->activeMembers()->whereKey($userIds)->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    /**
     * @return Builder<User>
     */
    private function activeMembers(): Builder
    {
        $businessId = $this->business->require();

        return User::query()->where('is_active', true)
            ->whereHas('businesses', fn (Builder $business) => $business->whereKey($businessId)->where('business_user.is_active', true));
    }
}
