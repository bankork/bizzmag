<?php

namespace App\Domain\Shared\Phone;

/**
 * Phone number normalisation to E.164 (+2348031234567).
 *
 * Nigerian numbers are accepted the way people write them: 0803 123 4567,
 * 08031234567, 234 803 123 4567 or +234 803 123 4567. Other countries must
 * be written with a leading +.
 */
final class PhoneNumber
{
    public static function normalise(?string $input): ?string
    {
        if ($input === null) {
            return null;
        }

        $value = preg_replace('/[\s\-().]/', '', trim($input)) ?? '';

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(?:\+?234|0)([789]\d{9})$/', $value, $matches) === 1) {
            return '+234'.$matches[1];
        }

        if (preg_match('/^\+(?!234)[1-9]\d{7,14}$/', $value) === 1) {
            return $value;
        }

        return null;
    }

    public static function isValid(?string $input): bool
    {
        return self::normalise($input) !== null;
    }

    /**
     * Whether the text looks like a phone number rather than an email.
     */
    public static function looksLikePhone(string $input): bool
    {
        return preg_match('/^[\d\s+\-().]+$/', trim($input)) === 1;
    }
}
