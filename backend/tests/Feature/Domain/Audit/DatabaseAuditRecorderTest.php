<?php

namespace Tests\Feature\Domain\Audit;

use App\Domain\Audit\AuditEvent;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class DatabaseAuditRecorderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_records_the_action_with_before_after_reason_and_request_id(): void
    {
        Context::add('request_id', '01JDEMO0000000000000000000');

        $this->app->make(AuditRecorder::class)->record(new AuditEntry(
            action: 'settings.tax_changed',
            subjectType: 'tax_setting',
            subjectId: 1,
            before: ['enabled' => false],
            after: ['enabled' => true, 'rate_bp' => 750],
            reason: 'Registered for VAT',
        ));

        $event = AuditEvent::query()->sole();
        $this->assertSame('settings.tax_changed', $event->action);
        $this->assertSame(['enabled' => false], $event->before);
        $this->assertSame(['enabled' => true, 'rate_bp' => 750], $event->after);
        $this->assertSame('Registered for VAT', $event->reason);
        $this->assertSame('01JDEMO0000000000000000000', $event->request_id);
    }

    public function test_an_event_disappears_when_the_business_change_rolls_back(): void
    {
        try {
            DB::transaction(function (): void {
                $this->app->make(AuditRecorder::class)->record(new AuditEntry(action: 'payment.recorded'));

                throw new RuntimeException('Payment failed.');
            });
        } catch (RuntimeException) {
        }

        $this->assertDatabaseCount('audit_events', 0);
    }

    public function test_events_cannot_be_changed_through_the_application(): void
    {
        $this->app->make(AuditRecorder::class)->record(new AuditEntry(action: 'invoice.finalised'));
        $event = AuditEvent::query()->sole();

        $this->expectException(LogicException::class);

        $event->update(['action' => 'invoice.voided']);
    }

    public function test_events_cannot_be_deleted_through_the_application(): void
    {
        $this->app->make(AuditRecorder::class)->record(new AuditEntry(action: 'invoice.finalised'));

        $this->expectException(LogicException::class);

        AuditEvent::query()->sole()->delete();
    }
}
