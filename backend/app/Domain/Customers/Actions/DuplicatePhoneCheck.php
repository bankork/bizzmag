<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Exceptions\DuplicatePhone;
use App\Domain\Customers\Models\Customer;

/**
 * The duplicate-phone warning (FR-CUS-009): a matching number stops the
 * save unless the user has confirmed it. Removed customers have no number,
 * so they never match.
 */
final class DuplicatePhoneCheck
{
    public function __invoke(?string $phone, bool $confirmed, ?int $except = null): void
    {
        if ($phone === null || $confirmed) {
            return;
        }

        $matches = Customer::query()
            ->where('phone_e164', $phone)
            ->when($except !== null, fn ($query) => $query->whereKeyNot($except))
            ->orderBy('id')
            ->limit(5)
            ->get(['id', 'name']);

        if ($matches->isNotEmpty()) {
            throw DuplicatePhone::matching($matches);
        }
    }
}
