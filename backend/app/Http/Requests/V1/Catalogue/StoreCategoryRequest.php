<?php

namespace App\Http\Requests\V1\Catalogue;

use App\Domain\Catalogue\Models\ItemType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ItemType::class)],
            'name' => ['required', 'string', 'max:80'],
        ];
    }
}
