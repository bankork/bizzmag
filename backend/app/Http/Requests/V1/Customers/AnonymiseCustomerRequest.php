<?php

namespace App\Http\Requests\V1\Customers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Removing personal data cannot be undone, so the owner types REMOVE.
 */
class AnonymiseCustomerRequest extends FormRequest
{
    public const CONFIRMATION = 'REMOVE';

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'confirm' => ['required', 'string', 'in:'.self::CONFIRMATION],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirm.required' => 'Type REMOVE to confirm.',
            'confirm.in' => 'Type REMOVE to confirm.',
        ];
    }
}
