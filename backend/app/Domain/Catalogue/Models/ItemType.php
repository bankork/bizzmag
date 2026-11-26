<?php

namespace App\Domain\Catalogue\Models;

/**
 * What a catalogue entry is (FR-SVC-002).
 */
enum ItemType: string
{
    case Service = 'service';
    case Product = 'product';
}
