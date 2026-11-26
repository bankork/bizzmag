<?php

namespace App\Http\Requests\V1\Identity\Concerns;

use App\Domain\Shared\Phone\PhoneNumber;
use Closure;
use Illuminate\Validation\Rule;

/**
 * Email and phone rules shared by the create and edit staff forms. The
 * phone is normalised to +234 form before validation, so 0803 123 4567 and
 * +2348031234567 are the same number.
 */
trait ValidatesStaffContact
{
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('phone')) {
            $raw = $this->input('phone');
            $merge['phone'] = is_string($raw) && trim($raw) !== '' ? (PhoneNumber::normalise($raw) ?? $raw) : null;
        }

        if ($this->has('email')) {
            $raw = $this->input('email');
            $merge['email'] = is_string($raw) && trim($raw) !== '' ? mb_strtolower(trim($raw)) : null;
        }

        $this->merge($merge);
    }

    /**
     * @return array<string, list<mixed>>
     */
    protected function contactRules(?int $ignoreUserId, bool $creating): array
    {
        return [
            'email' => [$creating ? 'required_without:phone' : 'sometimes', 'nullable', 'email:rfc', 'max:190', Rule::unique('users', 'email')->ignore($ignoreUserId)],
            'phone' => [$creating ? 'required_without:email' : 'sometimes', 'nullable', 'string', 'max:20', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== null && ! PhoneNumber::isValid((string) $value)) {
                    $fail('Enter a valid phone number, for example 0803 123 4567.');
                }
            }, Rule::unique('users', 'phone')->ignore($ignoreUserId)],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function contactMessages(): array
    {
        return [
            'email.required_without' => 'Enter an email or a phone number.',
            'phone.required_without' => 'Enter a phone number or an email.',
            'email.unique' => 'Someone already uses this email.',
            'phone.unique' => 'Someone already uses this phone number.',
        ];
    }
}
