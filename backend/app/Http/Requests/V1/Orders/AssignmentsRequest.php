<?php

namespace App\Http\Requests\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Who is expected to do a service. An empty list leaves it unassigned (FR-ORD-006).
 */
class AssignmentsRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'employee_ids' => ['present', 'array', 'max:10'],
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
