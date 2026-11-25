<?php

namespace App\Domain\Billing\Calculators;

enum DiscountKind: string
{
    case Fixed = 'fixed';
    case Percent = 'percent';
}
