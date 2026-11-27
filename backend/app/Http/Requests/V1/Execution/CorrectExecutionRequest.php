<?php

namespace App\Http\Requests\V1\Execution;

use App\Http\Requests\V1\Execution\Concerns\SplitRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A corrected record: who did the work, who was the primary, optionally a
 * different split, and why (FR-EXE-010).
 */
class CorrectExecutionRequest extends FormRequest
{
    use SplitRules;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->splitRules('sometimes'),
            'employee_ids' => ['required', 'array', 'min:1', 'max:11'],
            'employee_ids.*' => ['integer', 'distinct'],
            'primary_employee_id' => ['required', 'integer', 'in_array:employee_ids.*'],
        ];
    }

    /**
     * @return list<int>
     */
    public function employeeIds(): array
    {
        return array_values(array_map('intval', (array) $this->validated('employee_ids')));
    }

    public function primaryEmployeeId(): int
    {
        return (int) $this->validated('primary_employee_id');
    }
}
