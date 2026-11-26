<?php

namespace App\Http\Requests\V1\Identity;

use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Http\Requests\V1\Identity\Concerns\ValidatesStaffContact;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    use ValidatesStaffContact;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            ...$this->contactRules(null, creating: true),
            'password' => ['required', 'string', 'max:200', Password::defaults()],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('business_id', app(CurrentBusiness::class)->require())],
            'employee_profile' => ['nullable', 'array'],
            'employee_profile.display_name' => ['required_with:employee_profile', 'string', 'max:60'],
            'employee_profile.job_title' => ['nullable', 'string', 'max:60'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->contactMessages(),
            'role_ids.required' => 'Choose at least one role.',
            'role_ids.min' => 'Choose at least one role.',
            'employee_profile.display_name.required_with' => 'Enter the name customers and staff know them by.',
        ];
    }
}
