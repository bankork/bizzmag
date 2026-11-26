<?php

namespace App\Http\Requests\V1\Customers\Concerns;

use App\Domain\Shared\Phone\PhoneNumber;
use Closure;

/**
 * Customer fields (FR-CUS-002). Empty strings become null, so clearing a
 * field removes it.
 */
trait CustomerRules
{
    protected function prepareForValidation(): void
    {
        $merge = [];

        foreach (['phone', 'email', 'notes'] as $field) {
            if ($this->has($field)) {
                $raw = $this->input($field);
                $merge[$field] = is_string($raw) && trim($raw) !== '' ? trim($raw) : null;
            }
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function customerRules(string $presence): array
    {
        return [
            'name' => [$presence, 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== null && ! PhoneNumber::isValid((string) $value)) {
                    $fail('Enter a valid phone number, for example 0803 123 4567.');
                }
            }],
            'email' => ['sometimes', 'nullable', 'email:rfc', 'max:150'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'confirm_duplicate' => ['sometimes', 'boolean'],
        ];
    }
}
