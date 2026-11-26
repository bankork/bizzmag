<?php

namespace App\Domain\Identity\Support;

use App\Domain\Shared\Phone\PhoneNumber;
use App\Models\User;

/**
 * Finds a user from what they typed at sign-in: an email address or a
 * phone number in any common Nigerian format (P1 Q1).
 */
final class IdentifierResolver
{
    /**
     * The canonical form used for lookups and lockout counting.
     */
    public function normalise(string $identifier): string
    {
        $identifier = trim($identifier);

        if (! str_contains($identifier, '@') && PhoneNumber::looksLikePhone($identifier)) {
            return PhoneNumber::normalise($identifier) ?? $identifier;
        }

        return mb_strtolower($identifier);
    }

    public function find(string $identifier): ?User
    {
        $normalised = $this->normalise($identifier);

        if ($normalised === '') {
            return null;
        }

        $column = str_starts_with($normalised, '+') ? 'phone' : 'email';

        return User::query()->where($column, $normalised)->first();
    }
}
