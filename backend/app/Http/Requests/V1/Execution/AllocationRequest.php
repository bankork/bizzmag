<?php

namespace App\Http\Requests\V1\Execution;

use App\Http\Requests\V1\Execution\Concerns\SplitRules;
use Illuminate\Foundation\Http\FormRequest;

/**
 * A supervisor's change to one record's split (FR-ATTR-009).
 */
class AllocationRequest extends FormRequest
{
    use SplitRules;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            ...$this->splitRules('required'),
            'primary_employee_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    public function primaryEmployeeId(): ?int
    {
        $id = $this->validated('primary_employee_id');

        return $id === null ? null : (int) $id;
    }
}
