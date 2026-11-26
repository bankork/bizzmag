<?php

namespace App\Domain\Identity\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Which business a user works in. An installation normally has one
 * business, so the user's first active membership is their business.
 */
final class Membership
{
    public function activeBusinessId(User $user): ?int
    {
        $businessId = DB::table('business_user')
            ->where('user_id', $user->getKey())
            ->where('is_active', true)
            ->orderBy('id')
            ->value('business_id');

        return $businessId === null ? null : (int) $businessId;
    }

    public function isMember(User $user, int $businessId): bool
    {
        return DB::table('business_user')
            ->where('user_id', $user->getKey())
            ->where('business_id', $businessId)
            ->exists();
    }
}
