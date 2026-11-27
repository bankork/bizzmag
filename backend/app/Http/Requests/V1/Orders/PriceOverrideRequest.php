<?php

namespace App\Http\Requests\V1\Orders;

use App\Domain\Catalogue\Models\CatalogueItem;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A new unit price for one item on a visit, with the mandatory reason (FR-ORD-011).
 */
class PriceOverrideRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'price_kobo' => ['required', 'integer', 'min:0', 'max:'.CatalogueItem::MAX_PRICE_KOBO],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }
}
