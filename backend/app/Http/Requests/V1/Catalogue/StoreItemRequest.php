<?php

namespace App\Http\Requests\V1\Catalogue;

use App\Http\Requests\V1\Catalogue\Concerns\ItemRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A new service or product, or with parent_id a new variant of a service.
 */
class StoreItemRequest extends FormRequest
{
    use ItemRules;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->itemRules('required'),
            'parent_id' => ['sometimes', 'nullable', 'integer'],
            'category_id' => ['required_without:parent_id', 'nullable', 'integer'],
        ];
    }
}
