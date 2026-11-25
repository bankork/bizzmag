<?php

namespace App\Domain\Billing\Calculators;

/**
 * What kind of thing an invoice line is (PRD-7.2-1).
 */
enum LineType: string
{
    /** Work performed by staff. Requires execution and earns contribution. */
    case Service = 'service';

    /** Hair attachments or retail products. No execution, no contribution. */
    case Product = 'product';
}
