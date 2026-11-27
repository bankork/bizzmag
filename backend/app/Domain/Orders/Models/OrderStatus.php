<?php

namespace App\Domain\Orders\Models;

/**
 * A visit's lifecycle (SRS §6.1). Payment status is calculated, not a state.
 */
enum OrderStatus: string
{
    case Open = 'open';
    case Finalised = 'finalised';
    case Cancelled = 'cancelled';
}
