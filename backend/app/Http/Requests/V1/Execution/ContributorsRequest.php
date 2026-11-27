<?php

namespace App\Http\Requests\V1\Execution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Everyone who should be on a record, edited by the person who recorded it.
 */
class ContributorsRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1', 'max:11'],
            'employee_ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return list<int>
     */
    public function employeeIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('employee_ids')));
    }
}
