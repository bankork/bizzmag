<?php

namespace App\Http\Requests\V1\Identity;

use Illuminate\Foundation\Http\FormRequest;

class DisableUserRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return ['reason' => ['nullable', 'string', 'max:500']];
    }
}
