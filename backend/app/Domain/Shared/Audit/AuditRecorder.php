<?php

namespace App\Domain\Shared\Audit;

/**
 * Records audit events inside the caller's transaction (TDR-11).
 *
 * Every Action calls this explicitly, so the event carries intent and
 * reason, and disappears if the action rolls back.
 */
interface AuditRecorder
{
    public function record(AuditEntry $entry): void;
}
