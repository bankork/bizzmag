<?php

namespace App\Domain\Customers\Exceptions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Exceptions\DomainError;
use Illuminate\Support\Collection;

/**
 * Another customer already has this phone number (FR-CUS-009). The desk
 * can pick the existing customer or confirm and save anyway.
 */
final class DuplicatePhone extends DomainError
{
    /**
     * @param  Collection<int, Customer>  $matches
     */
    public static function matching(Collection $matches): self
    {
        $first = $matches->first();
        $others = $matches->count() - 1;
        $who = $first?->name.($others > 0 ? " and {$others} other ".($others === 1 ? 'customer' : 'customers') : '');

        return new self("This number already belongs to {$who}. Add anyway?", [
            'matches' => $matches->map(fn (Customer $customer) => ['id' => $customer->id, 'name' => $customer->name])->values()->all(),
        ]);
    }

    public function errorCode(): string
    {
        return 'DUPLICATE_PHONE';
    }

    public function httpStatus(): int
    {
        return 409;
    }

    protected function defaultMessage(): string
    {
        return 'This number already belongs to another customer. Add anyway?';
    }
}
