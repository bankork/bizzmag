<?php

namespace App\Http\Requests\V1\Catalogue;

use App\Http\Requests\V1\Catalogue\Concerns\ItemRules;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
{
    use ItemRules;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            ...$this->itemRules('sometimes'),
        ];
    }
}
