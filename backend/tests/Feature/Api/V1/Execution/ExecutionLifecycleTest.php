<?php

namespace Tests\Feature\Api\V1\Execution;

use App\Domain\Audit\AuditEvent;
use App\Domain\Business\Models\Business;
use App\Domain\Execution\Models\Execution;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Orders\Models\OrderItem;
use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;
use Tests\Support\BuildsVisits;

/**
 * What happens to a record after it is made (SRS §6.3, FR-EXE-009 to 012,
 * FR-ATTR-009, FR-ATTR-013, BR-040, §14.2, §14.24).
 */
class ExecutionLifecycleTest extends ApiTestCase
{
    use BuildsVisits;

    /**
     * @var array{business: Business, owner: User, supervisor: User, cashier: User, ada: User, bisi: User, chi: User}
     */
    private array $salon;

    private OrderItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = $this->salonWithStaff();
        $order = $this->openVisit($this->salon['business'], $this->salon['cashier'], [['catalogue_item_id' => $this->service($this->salon['business'], 'Knotless braids', 4_000_000)->id]]);
        $this->item = $this->firstItem($order);
    }

    public function test_the_person_who_recorded_the_work_adds_and_removes_colleagues_who_are_notified(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);

        $this->as($this->salon['ada'])->putJson("api/v1/executions/{$execution->id}/contributors", ['employee_ids' => $this->ids('ada', 'chi')])
            ->assertOk()
            ->assertJsonPath('data.items.0.work.contributors.1.name', 'Chi');

        $this->assertSame(['Ada' => 5000, 'Chi' => 5000], $this->sharesByName($execution));
        $this->assertSame(['execution.added'], $this->notificationTypes($this->salon['chi']));
        $this->assertContains('execution.removed', $this->notificationTypes($this->salon['bisi']));
        $this->assertTrue(AuditEvent::query()->where('action', 'execution.contributors_changed')->exists());
    }

    public function test_nobody_else_can_edit_the_people_on_a_record(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);

        $this->as($this->salon['bisi'])->putJson("api/v1/executions/{$execution->id}/contributors", ['employee_ids' => $this->ids('ada', 'bisi', 'chi')])
            ->assertStatus(403);
    }

    public function test_the_person_who_recorded_cannot_remove_themselves_or_the_primary(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);

        $this->as($this->salon['ada'])->putJson("api/v1/executions/{$execution->id}/contributors", ['employee_ids' => $this->ids('bisi')])
            ->assertStatus(422)->assertJsonPath('code', 'ALLOCATION_INVALID');
    }

    public function test_a_supervisor_overrides_the_split_with_custom_percentages_and_a_reason(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);
        [$ada, $bisi] = $this->ids('ada', 'bisi');

        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/allocation", [
            'method' => 'custom', 'reason' => 'Bisi did most of the length',
            'shares' => [['employee_id' => $ada, 'share_bp' => 4000], ['employee_id' => $bisi, 'share_bp' => 5000]],
        ])->assertStatus(422)->assertJsonPath('code', 'ALLOCATION_INVALID');

        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/allocation", [
            'method' => 'custom', 'reason' => 'Bisi did most of the length',
            'shares' => [['employee_id' => $ada, 'share_bp' => 4000], ['employee_id' => $bisi, 'share_bp' => 6000]],
        ])->assertOk()->assertJsonPath('data.items.0.work.allocation_method', 'custom');

        $this->assertSame(['Ada' => 4000, 'Bisi' => 6000], $this->sharesByName($execution));
        $event = AuditEvent::query()->where('action', 'allocation.overridden')->sole();
        $this->assertSame('Bisi did most of the length', $event->reason);
        $this->assertEquals(['method' => 'equal', 'primary_employee_id' => $ada, 'shares' => [$ada => 5000, $bisi => 5000]], $event->before);
    }

    public function test_a_hand_set_split_cannot_be_changed_by_joining(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);
        [$ada, $bisi] = $this->ids('ada', 'bisi');
        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/allocation", [
            'method' => 'custom', 'reason' => 'Agreed at the desk',
            'shares' => [['employee_id' => $ada, 'share_bp' => 7000], ['employee_id' => $bisi, 'share_bp' => 3000]],
        ])->assertOk();

        $this->as($this->salon['chi'])->postJson("api/v1/executions/{$execution->id}/join")->assertStatus(409)->assertJsonPath('code', 'SPLIT_SET_BY_HAND');
    }

    public function test_a_supervisor_cannot_override_correct_or_cancel_their_own_record_but_the_owner_can_with_a_flag(): void
    {
        $execution = $this->record($this->salon['supervisor'], $this->item, [$this->salon['ada']]);

        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/allocation", ['method' => 'primary_assistant', 'primary_share_bp' => 7000, 'reason' => 'Mine'])
            ->assertStatus(403)->assertJsonPath('code', 'SEGREGATION_OF_DUTIES');
        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/cancel", ['reason' => 'Mine'])
            ->assertStatus(403)->assertJsonPath('code', 'SEGREGATION_OF_DUTIES');

        $this->as($this->salon['owner'])->postJson("api/v1/executions/{$execution->id}/cancel", ['reason' => 'Recorded twice by mistake'])->assertOk();
        $this->assertFalse(AuditEvent::query()->where('action', 'execution.cancelled')->sole()->sod_exempt, 'The owner did not record it, so no exemption was needed.');
    }

    public function test_the_owner_may_cancel_their_own_record_and_the_exemption_is_flagged(): void
    {
        $execution = $this->record($this->salon['owner'], $this->item, primary: $this->salon['ada']);

        $this->as($this->salon['owner'])->postJson("api/v1/executions/{$execution->id}/cancel", ['reason' => 'Wrong service chosen'])->assertOk();

        $this->assertTrue(AuditEvent::query()->where('action', 'execution.cancelled')->sole()->sod_exempt);
    }

    public function test_a_named_employee_disputes_and_a_supervisor_dismisses_the_dispute(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);

        $this->as($this->salon['chi'])->postJson("api/v1/executions/{$execution->id}/dispute", ['reason' => 'Not me'])->assertStatus(403);
        $this->as($this->salon['bisi'])->postJson("api/v1/executions/{$execution->id}/dispute", ['reason' => 'I only washed, I did not braid'])
            ->assertOk()->assertJsonPath('data.items.0.work.status', 'disputed')->assertJsonPath('data.progress.disputed', 1);

        $this->assertContains('execution.disputed', $this->notificationTypes($this->salon['ada']));
        $this->assertContains('execution.disputed', $this->notificationTypes($this->salon['supervisor']));
        $this->assertNotContains('execution.disputed', $this->notificationTypes($this->salon['bisi']));

        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/dismiss-dispute", ['reason' => 'Checked with the customer'])
            ->assertOk()->assertJsonPath('data.items.0.work.status', 'confirmed');
        $this->assertContains('execution.dispute_dismissed', $this->notificationTypes($this->salon['bisi']));
    }

    public function test_the_person_who_disputed_cannot_resolve_the_dispute(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);
        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/dispute", ['reason' => 'Looks wrong'])->assertOk();

        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/dismiss-dispute", ['reason' => 'Fine after all'])
            ->assertStatus(403)->assertJsonPath('code', 'SEGREGATION_OF_DUTIES');
    }

    public function test_a_correction_replaces_the_record_and_keeps_the_original_visible(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);
        $this->as($this->salon['bisi'])->postJson("api/v1/executions/{$execution->id}/dispute", ['reason' => 'Chi did it with Ada, not me'])->assertOk();
        [$ada, $chi] = $this->ids('ada', 'chi');

        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/correct", [
            'employee_ids' => [$ada, $chi], 'primary_employee_id' => $ada, 'reason' => 'Chi assisted, not Bisi',
        ])->assertOk()
            ->assertJsonPath('data.items.0.work.supersedes_id', $execution->id)
            ->assertJsonPath('data.items.0.work.contributors.1.name', 'Chi')
            ->assertJsonPath('data.items.0.history.0.id', $execution->id)
            ->assertJsonPath('data.items.0.history.0.status', 'superseded');

        $this->assertSame('superseded', $execution->refresh()->status->value);
        $this->assertSame(['Ada' => 5000, 'Bisi' => 5000], $this->sharesByName($execution), 'The original keeps its people and shares.');
        $this->assertContains('execution.corrected', $this->notificationTypes($this->salon['bisi']));
        $this->assertContains('execution.corrected', $this->notificationTypes($this->salon['chi']));
    }

    public function test_a_cancelled_record_leaves_the_service_waiting_again(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item);

        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/cancel", ['reason' => 'Recorded on the wrong visit'])
            ->assertOk()
            ->assertJsonPath('data.items.0.work', null)
            ->assertJsonPath('data.items.0.history.0.status', 'cancelled')
            ->assertJsonPath('data.progress.waiting', 1);
    }

    public function test_a_cashier_cannot_cancel_a_recorded_service_through_the_api(): void
    {
        $this->record($this->salon['ada'], $this->item);

        $this->as($this->salon['cashier'])->postJson("api/v1/order-items/{$this->item->id}/cancel", ['reason' => 'Customer did not like it'])
            ->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');

        $this->assertSame('requested', $this->item->refresh()->status->value);
        $this->assertSame('confirmed', Execution::query()->withoutGlobalScopes()->sole()->status->value);
        $this->assertTrue(AuditEvent::query()->where('action', 'access.denied')->where('actor_id', $this->salon['cashier']->id)->get()
            ->contains(fn (AuditEvent $event) => $event->after['permission'] === 'execution.correct'));
    }

    public function test_a_supervisor_cancels_a_recorded_service_and_its_record_together(): void
    {
        $this->record($this->salon['ada'], $this->item);

        $this->as($this->salon['supervisor'])->postJson("api/v1/order-items/{$this->item->id}/cancel", ['reason' => 'Customer refused the style'])
            ->assertOk()->assertJsonPath('data.items.0.status', 'cancelled');

        $this->assertSame('cancelled', Execution::query()->withoutGlobalScopes()->sole()->status->value);
        $this->assertTrue(AuditEvent::query()->where('action', 'order.item_cancelled_executed')->exists());
        $this->assertContains('execution.cancelled', $this->notificationTypes($this->salon['ada']));
    }

    public function test_cancelling_a_visit_with_recorded_work_needs_execution_correct(): void
    {
        $this->record($this->salon['ada'], $this->item);
        $order = $this->item->service_order_id;

        $this->as($this->salon['supervisor'])->postJson("api/v1/orders/{$order}/cancel", ['reason' => 'Customer walked out'])
            ->assertOk()->assertJsonPath('data.status', 'cancelled')->assertJsonPath('data.items.0.status', 'cancelled');

        $this->assertSame('cancelled', Execution::query()->withoutGlobalScopes()->sole()->status->value);
    }

    public function test_every_step_is_audited_against_the_visit(): void
    {
        $execution = $this->record($this->salon['ada'], $this->item, [$this->salon['bisi']]);
        $this->as($this->salon['bisi'])->postJson("api/v1/executions/{$execution->id}/dispute", ['reason' => 'Wrong'])->assertOk();
        $this->as($this->salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/correct", ['employee_ids' => $this->ids('ada'), 'primary_employee_id' => $this->ids('ada')[0], 'reason' => 'Ada alone'])->assertOk();

        $actions = AuditEvent::query()->where('order_id', $this->item->service_order_id)->orderBy('id')->pluck('action')->all();
        $this->assertSame(['order.created', 'execution.submitted', 'execution.disputed', 'execution.corrected'], $actions);
    }

    /**
     * @return list<int>
     */
    private function ids(string ...$people): array
    {
        return array_map(fn (string $person) => $this->profileOf($this->salon[$person])->id, $people);
    }

    /**
     * @return list<string>
     */
    private function notificationTypes(User $user): array
    {
        return InAppNotification::query()->withoutGlobalScopes()->where('user_id', $user->id)->orderBy('id')->pluck('type')->all();
    }
}
