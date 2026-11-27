<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Actions that need a written reason: cancellations, rejections, disputes
 * and their resolution (SRS §6).
 */
class ReasonRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:500']];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['reason.required' => 'Give a reason.', 'reason.min' => 'Give a reason of at least 3 characters.'];
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
