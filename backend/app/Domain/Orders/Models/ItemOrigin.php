<?php

namespace App\Domain\Orders\Models;

/**
 * How an item came onto the visit.
 */
enum ItemOrigin: string
{
    /** Chosen when the visit was opened. */
    case Order = 'order';

    /** Added later by the desk (FR-ORD-012). */
    case DeskAdd = 'desk_add';

    /** Requested by staff and waiting for, or given, approval (FR-ADD-001). */
    case AdditionalRequest = 'additional_request';
}
