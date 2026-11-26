<?php

namespace App\Http\Requests\V1\Business;

use App\Domain\Shared\Tax\TaxMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The single tax (FR-TAX-001, FR-TAX-008). The rate is in basis points:
 * 750 is 7.5%.
 */
class UpdateTaxRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            'enabled' => ['required', 'boolean'],
            'name' => ['required', 'string', 'max:30'],
            'rate_bp' => ['required', 'integer', 'min:'.($this->boolean('enabled') ? 1 : 0), 'max:10000'],
            'mode' => ['required', Rule::enum(TaxMode::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rate_bp.min' => 'Enter a tax rate above 0%, or switch tax off.',
            'rate_bp.max' => 'The tax rate cannot be more than 100%.',
        ];
    }
}
