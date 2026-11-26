<?php

namespace App\Domain\Catalogue\Models;

/**
 * The default way a service's credit is shared between the staff who do
 * it (FR-ATTR-004, FR-ATTR-007). Custom percentages are chosen per visit
 * with allocation.override, not in the catalogue (P2 Q2). The values match
 * the allocation calculator's methods.
 */
enum SplitMethod: string
{
    case Equal = 'equal';
    case PrimaryAssistant = 'primary_assistant';

    /**
     * The primary's share when none is set (FR-ATTR-007): 50%.
     */
    public const DEFAULT_PRIMARY_SHARE_BP = 5000;
}
