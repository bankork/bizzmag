<?php

namespace App\Domain\Audit;

use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

/**
 * Writes audit events to the audit_events table on the current connection,
 * so the event commits or rolls back together with the business change.
 *
 * The business and the actor default to the current business and the
 * signed-in user when the entry does not name them.
 */
final class DatabaseAuditRecorder implements AuditRecorder
{
    public function __construct(
        private readonly CurrentBusiness $business,
        private readonly ?Request $request = null,
    ) {}

    public function record(AuditEntry $entry): void
    {
        AuditEvent::query()->create([
            'business_id' => $entry->businessId ?? $this->business->id(),
            'actor_id' => $entry->actorId ?? $this->actorId(),
            'device_id' => $entry->deviceId,
            'action' => $entry->action,
            'subject_type' => $entry->subjectType,
            'subject_id' => $entry->subjectId,
            'order_id' => $entry->orderId,
            'before' => $entry->before,
            'after' => $entry->after,
            'reason' => $entry->reason,
            'sod_exempt' => $entry->sodExempt,
            'request_id' => Context::get('request_id'),
            'ip_address' => app()->runningInConsole() ? null : $this->request?->ip(),
            'created_at' => now(),
        ]);
    }

    private function actorId(): ?int
    {
        $id = Auth::id();

        return is_int($id) ? $id : null;
    }
}
