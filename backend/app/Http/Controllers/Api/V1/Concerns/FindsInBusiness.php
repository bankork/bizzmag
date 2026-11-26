<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Domain\Identity\Models\Role;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;

/**
 * Looks records up inside the current business only. A record from
 * another business is reported as not found, never as forbidden, so its
 * existence is not revealed (SEC-003).
 */
trait FindsInBusiness
{
    protected function userInBusiness(int $id): User
    {
        return User::query()->inBusiness(app(CurrentBusiness::class)->require())->whereKey($id)->first()
            ?? throw new RecordNotFound;
    }

    protected function roleInBusiness(int $id): Role
    {
        return Role::query()->forBusiness(app(CurrentBusiness::class)->require())->whereKey($id)->first()
            ?? throw new RecordNotFound;
    }
}
