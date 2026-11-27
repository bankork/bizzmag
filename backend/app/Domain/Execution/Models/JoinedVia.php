<?php

namespace App\Domain\Execution\Models;

/**
 * How a contributor came onto a record.
 */
enum JoinedVia: string
{
    case Submit = 'submit';
    case Join = 'join';
    case Edit = 'edit';
    case Override = 'override';
    case Correction = 'correction';
}
