<?php

namespace App\Http\Requests\V1\Execution\Concerns;

use App\Domain\Execution\Allocation\AllocationMethod;
use Illuminate\Validation\Rule;

/**
 * A credit split chosen by a supervisor (FR-ATTR-005, 007, 009). Custom
 * percentages arrive as basis points: 33.34% is 3334.
 */
trait SplitRules
{
    /**
     * @return array<string, list<mixed>>
     */
    protected function splitRules(string $methodPresence): array
    {
        return [
            'method' => [$methodPresence, Rule::enum(AllocationMethod::class)],
            'primary_share_bp' => ['sometimes', 'nullable', 'integer', 'min:100', 'max:10000'],
            'shares' => ['required_if:method,custom', 'array', 'max:11'],
            'shares.*.employee_id' => ['required', 'integer', 'distinct'],
            'shares.*.share_bp' => ['required', 'integer', 'min:1', 'max:10000'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function splitMethod(): ?AllocationMethod
    {
        $method = $this->validated('method');

        return is_string($method) ? AllocationMethod::from($method) : null;
    }

    public function primaryShareBp(): ?int
    {
        $share = $this->validated('primary_share_bp');

        return $share === null ? null : (int) $share;
    }

    /**
     * @return array<int, int>|null
     */
    public function customShares(): ?array
    {
        if ($this->splitMethod() !== AllocationMethod::Custom) {
            return null;
        }

        $shares = [];

        foreach ((array) $this->validated('shares') as $share) {
            $shares[(int) $share['employee_id']] = (int) $share['share_bp'];
        }

        return $shares;
    }

    public function reason(): string
    {
        return trim((string) $this->validated('reason'));
    }
}
