<?php

namespace App\Domain\Execution\Allocation;

/**
 * One contributor's calculated credit for one service line.
 */
final readonly class ContributorAllocation
{
    public function __construct(
        public int $employeeId,
        public bool $isPrimary,
        public int $shareBasisPoints,
        /** Share of the line's net value. Adds up across contributors to the line net value. */
        public int $allocatedKobo,
        /** The full line net value. Informational; never summed as revenue. */
        public int $participatedKobo,
    ) {}

    /**
     * @return array{employee_id: int, is_primary: bool, share_bp: int, allocated_kobo: int, participated_kobo: int}
     */
    public function toArray(): array
    {
        return [
            'employee_id' => $this->employeeId,
            'is_primary' => $this->isPrimary,
            'share_bp' => $this->shareBasisPoints,
            'allocated_kobo' => $this->allocatedKobo,
            'participated_kobo' => $this->participatedKobo,
        ];
    }
}
