<?php

namespace App\Domain\Identity\Support;

use App\Models\User;

/**
 * The discount cap of a user (FR-IAM-009): with several roles, the highest
 * cap applies. Null means unlimited.
 */
final class DiscountCap
{
    /**
     * @return array{percent_bp: ?int, per_invoice_kobo: ?int}
     */
    public static function for(User $user): array
    {
        $roles = $user->roles()->get(['roles.id', 'discount_cap_bp', 'discount_cap_kobo']);

        if ($roles->isEmpty()) {
            return ['percent_bp' => 0, 'per_invoice_kobo' => 0];
        }

        return [
            'percent_bp' => self::highest($roles->pluck('discount_cap_bp')->all()),
            'per_invoice_kobo' => self::highest($roles->pluck('discount_cap_kobo')->all()),
        ];
    }

    /**
     * @param  list<int|null>  $caps
     */
    private static function highest(array $caps): ?int
    {
        if (in_array(null, $caps, true)) {
            return null;
        }

        return (int) max($caps);
    }
}
