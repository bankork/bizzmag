<?php

namespace App\Http\Requests\V1\Orders;

use App\Domain\Orders\Support\ItemSnapshot;
use Illuminate\Foundation\Http\FormRequest;

/**
 * One item to add to an open visit (FR-ORD-012), or for an extra-service
 * request (FR-ADD-001) with its optional note.
 */
class AddItemRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'catalogue_item_id' => ['required', 'integer'],
            'quantity' => ['sometimes', 'integer', 'min:1', 'max:'.ItemSnapshot::MAX_QUANTITY],
            'employee_ids' => ['sometimes', 'array', 'max:10'],
            'employee_ids.*' => ['integer', 'distinct'],
            'note' => ['sometimes', 'nullable', 'string', 'max:300'],
        ];
    }
}
