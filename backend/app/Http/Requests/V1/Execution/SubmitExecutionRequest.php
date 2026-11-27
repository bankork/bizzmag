<?php

namespace App\Http\Requests\V1\Execution;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Recording a service: the colleagues who worked on it, and the primary
 * when the person recording does no salon work (P3 Q1).
 */
class SubmitExecutionRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'contributor_employee_ids' => ['sometimes', 'array', 'max:10'],
            'contributor_employee_ids.*' => ['integer', 'distinct'],
            'primary_employee_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /**
     * @return list<int>
     */
    public function colleagueIds(): array
    {
        return array_values(array_map('intval', (array) ($this->validated('contributor_employee_ids') ?? [])));
    }

    public function primaryEmployeeId(): ?int
    {
        $id = $this->validated('primary_employee_id');

        return $id === null ? null : (int) $id;
    }
}
