<?php

namespace App\Domain\Execution\Allocation;

final readonly class ContributorInput
{
    /**
     * @param  int|null  $customShareBasisPoints  Required only for the Custom method.
     */
    public function __construct(
        public int $employeeId,
        public bool $isPrimary = false,
        public ?int $customShareBasisPoints = null,
    ) {}
}
