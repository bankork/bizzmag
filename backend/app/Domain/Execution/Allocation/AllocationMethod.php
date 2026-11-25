<?php

namespace App\Domain\Execution\Allocation;

/**
 * How credit for one service is split between contributors (PRD-7.5-3).
 */
enum AllocationMethod: string
{
    /** Everyone gets the same share. */
    case Equal = 'equal';

    /** The primary gets a configured percentage; assistants share the rest equally. */
    case PrimaryAssistant = 'primary_assistant';

    /** Shares entered by a supervisor, totalling exactly 100%. */
    case Custom = 'custom';
}
