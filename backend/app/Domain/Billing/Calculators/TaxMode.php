<?php

namespace App\Domain\Billing\Calculators;

enum TaxMode: string
{
    /** Tax is added on top of the price. */
    case Exclusive = 'exclusive';

    /** The price already contains the tax. */
    case Inclusive = 'inclusive';
}
