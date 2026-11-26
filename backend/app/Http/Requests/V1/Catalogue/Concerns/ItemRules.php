<?php

namespace App\Http\Requests\V1\Catalogue\Concerns;

use App\Domain\Catalogue\Models\SplitMethod;
use Illuminate\Validation\Rule;

/**
 * Field rules shared by the create and edit item forms. Prices are whole
 * kobo, from ₦0 to ₦100,000,000 (FR-SVC-008). The primary share is in basis
 * points: 6000 is 60%.
 */
trait ItemRules
{
    public const MAX_PRICE_KOBO = 10_000_000_000;

    /**
     * @return array<string, list<mixed>>
     */
    protected function itemRules(string $presence): array
    {
        return [
            'name' => [$presence, 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'category_id' => ['sometimes', 'integer'],
            'has_variants' => ['sometimes', 'boolean'],
            'price_kobo' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:'.self::MAX_PRICE_KOBO],
            'taxable' => ['sometimes', 'boolean'],
            'quantity_allowed' => ['sometimes', 'boolean'],
            'allocation_method' => ['sometimes', 'nullable', Rule::enum(SplitMethod::class)],
            'primary_share_bp' => ['sometimes', 'nullable', 'integer', 'min:100', 'max:10000'],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'price_kobo.min' => 'A price cannot be below ₦0.',
            'price_kobo.max' => 'A price cannot be above ₦100,000,000.',
            'primary_share_bp.min' => 'The primary\'s share must be between 1% and 100%.',
            'primary_share_bp.max' => 'The primary\'s share must be between 1% and 100%.',
        ];
    }
}
