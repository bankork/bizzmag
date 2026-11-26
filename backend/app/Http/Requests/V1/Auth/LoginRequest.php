<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Sign in with an email address or a phone number (P1 Q1).
 */
class LoginRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identifier.required' => 'Enter your email or phone number.',
            'password.required' => 'Enter your password.',
        ];
    }
}
