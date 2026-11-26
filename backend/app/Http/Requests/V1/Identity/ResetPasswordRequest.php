<?php

namespace App\Http\Requests\V1\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetPasswordRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['password' => ['required', 'string', 'max:200', Password::defaults()]];
    }
}
