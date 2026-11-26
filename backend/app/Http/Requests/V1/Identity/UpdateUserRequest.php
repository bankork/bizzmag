<?php

namespace App\Http\Requests\V1\Identity;

use App\Http\Requests\V1\Identity\Concerns\ValidatesStaffContact;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    use ValidatesStaffContact;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $userId = (int) $this->route('user');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            ...$this->contactRules($userId, creating: false),
            'employee_profile' => ['nullable', 'array'],
            'employee_profile.display_name' => ['sometimes', 'required', 'string', 'max:60'],
            'employee_profile.job_title' => ['sometimes', 'nullable', 'string', 'max:60'],
            'employee_profile.is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->contactMessages();
    }
}
