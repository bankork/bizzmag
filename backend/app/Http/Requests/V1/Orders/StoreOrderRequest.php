<?php

namespace App\Http\Requests\V1\Orders;

use App\Domain\Orders\Support\ItemSnapshot;
use App\Domain\Shared\Phone\PhoneNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A new visit: a registered customer or a guest with an optional name and
 * phone (FR-CUS-003, FR-CUS-005), and one or more items (FR-ORD-002).
 */
class StoreOrderRequest extends FormRequest
{
    public const MAX_ITEMS = 30;

    protected function prepareForValidation(): void
    {
        foreach (['guest_name', 'guest_phone'] as $field) {
            if ($this->has($field)) {
                $raw = $this->input($field);
                $this->merge([$field => is_string($raw) && trim($raw) !== '' ? trim($raw) : null]);
            }
        }
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'prohibits:guest_name,guest_phone'],
            'guest_name' => ['nullable', 'string', 'max:100'],
            'guest_phone' => ['nullable', 'string', 'max:30', function (string $attribute, mixed $value, Closure $fail): void {
                if ($value !== null && ! PhoneNumber::isValid((string) $value)) {
                    $fail('Enter a valid phone number, for example 0803 123 4567.');
                }
            }],
            'items' => ['required', 'array', 'min:1', 'max:'.self::MAX_ITEMS],
            'items.*.catalogue_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['sometimes', 'integer', 'min:1', 'max:'.ItemSnapshot::MAX_QUANTITY],
            'items.*.employee_ids' => ['sometimes', 'array', 'max:10'],
            'items.*.employee_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['items.required' => 'Add at least one service or product.', 'items.min' => 'Add at least one service or product.'];
    }
}
