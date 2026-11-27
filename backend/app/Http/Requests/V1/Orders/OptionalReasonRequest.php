<?php

namespace App\Http\Requests\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Approving or withdrawing a request, where a reason is welcome but not required.
 */
class OptionalReasonRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['reason' => ['sometimes', 'nullable', 'string', 'max:500']];
    }

    public function reason(): ?string
    {
        $reason = $this->validated('reason');

        return is_string($reason) && trim($reason) !== '' ? trim($reason) : null;
    }
}
