<?php

namespace App\Http\Requests\V1\Business;

use App\Domain\Business\Settings\SettingsCatalogue;
use App\Domain\Shared\Phone\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Partial save of the business profile and settings. Send only what
 * changed, with the version the screen was loaded with.
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'version' => ['required', 'integer', 'min:1'],
            'profile' => ['sometimes', 'array'],
            'profile.name' => ['sometimes', 'required', 'string', 'max:120'],
            'profile.phone' => ['sometimes', 'nullable', 'string', 'max:20', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== null && ! PhoneNumber::isValid((string) $value)) {
                    $fail('Enter a valid phone number, for example 0803 123 4567.');
                }
            }],
            'profile.email' => ['sometimes', 'nullable', 'email:rfc', 'max:190'],
            'profile.address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'profile.receipt_footer' => ['sometimes', 'nullable', 'string', 'max:500'],
            'profile.invoice_footer' => ['sometimes', 'nullable', 'string', 'max:500'],
            'profile.timezone' => ['sometimes', 'required', 'timezone:all'],
        ];

        foreach (SettingsCatalogue::keys() as $key) {
            $rules[$key] = ['sometimes', 'required', ...SettingsCatalogue::rules($key)];
        }

        return $rules;
    }

    /**
     * @return array<string, int|string>
     */
    public function settingValues(): array
    {
        $values = [];

        foreach (SettingsCatalogue::keys() as $key) {
            if ($this->has($key)) {
                $value = $this->input($key);
                $values[$key] = is_numeric($value) && ! str_starts_with($key, 'numbering.') ? (int) $value : (string) $value;
            }
        }

        return $values;
    }

    /**
     * @return array<string, string|null>
     */
    public function profileValues(): array
    {
        /** @var array<string, string|null> $profile */
        $profile = $this->validated('profile', []);

        if (array_key_exists('phone', $profile) && $profile['phone'] !== null) {
            $profile['phone'] = PhoneNumber::normalise($profile['phone']);
        }

        return $profile;
    }
}
