<?php

namespace Tests\Feature\Api\V1\Orders;

use App\Domain\Audit\AuditEvent;
use App\Domain\Business\Models\Business;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\V1\ApiTestCase;
use Tests\Support\BuildsVisits;

/**
 * Extra services requested by staff (SRS §4.8, §6.2, §14.3, BR-040).
 */
class AdditionalServiceTest extends ApiTestCase
{
    use BuildsVisits;

    /**
     * @var array{business: Business, owner: User, supervisor: User, cashier: User, ada: User, bisi: User, chi: User}
     */
    private array $salon;

    private ServiceOrder $order;

    private int $washId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->salon = $this->salonWithStaff();
        $this->order = $this->openVisit($this->salon['business'], $this->salon['cashier'], [['catalogue_item_id' => $this->service($this->salon['business'], 'Cornrows', 800_000)->id]]);
        $this->washId = $this->service($this->salon['business'], 'Hair wash', 300_000)->id;
    }

    public function test_a_request_waits_for_approval_is_not_billable_and_reaches_the_approvers(): void
    {
        $this->request($this->salon['ada'], 'Customer also wants a wash')
            ->assertCreated()
            ->assertJsonPath('data.items.1.status', 'pending_approval')
            ->assertJsonPath('data.items.1.request_note', 'Customer also wants a wash')
            ->assertJsonPath('data.progress.pending_approval', 1);

        $this->as($this->salon['cashier'])->getJson("api/v1/orders/{$this->order->id}")->assertJsonPath('data.subtotal_kobo', 800_000);

        foreach (['owner', 'supervisor'] as $approver) {
            $this->assertSame(['additional.pending'], $this->types($this->salon[$approver]));
        }

        $this->assertSame([], $this->types($this->salon['cashier']));
        $this->assertSame([], $this->types($this->salon['ada']));

        $this->as($this->salon['supervisor'])->getJson('api/v1/approvals')
            ->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Hair wash')
            ->assertJsonPath('data.0.requested_by.name', 'Ada')
            ->assertJsonPath('data.0.order.reference', $this->order->reference);
    }

    public function test_approval_makes_it_billable_and_tells_the_requester(): void
    {
        $item = $this->pendingItem();

        $this->as($this->salon['supervisor'])->postJson("api/v1/order-items/{$item->id}/approve")
            ->assertOk()->assertJsonPath('data.items.1.status', 'approved')->assertJsonPath('data.subtotal_kobo', 1_100_000);

        $this->assertSame(['additional.decided'], $this->types($this->salon['ada']));
        $this->as($this->salon['supervisor'])->getJson('api/v1/approvals')->assertJsonCount(0, 'data');
        $this->as($this->salon['supervisor'])->postJson("api/v1/order-items/{$item->id}/approve")->assertStatus(409)->assertJsonPath('code', 'ALREADY_DECIDED');
    }

    public function test_a_rejection_needs_a_reason_and_is_never_billable(): void
    {
        $item = $this->pendingItem();

        $this->as($this->salon['supervisor'])->postJson("api/v1/order-items/{$item->id}/reject", [])->assertStatus(422)->assertJsonValidationErrors('reason');
        $this->as($this->salon['supervisor'])->postJson("api/v1/order-items/{$item->id}/reject", ['reason' => 'No time left today'])
            ->assertOk()->assertJsonPath('data.items.1.status', 'rejected')->assertJsonPath('data.items.1.decision_reason', 'No time left today')
            ->assertJsonPath('data.subtotal_kobo', 800_000);

        $event = AuditEvent::query()->where('action', 'additional.rejected')->sole();
        $this->assertSame($this->salon['supervisor']->id, $event->actor_id);
        $this->assertSame('No time left today', $event->reason);
    }

    public function test_nobody_approves_their_own_request_except_the_owner_with_a_flag(): void
    {
        $this->as($this->salon['supervisor'])->postJson("api/v1/orders/{$this->order->id}/additional-requests", ['catalogue_item_id' => $this->washId])->assertStatus(403);

        $own = OrderItem::query()->withoutGlobalScopes()->create([
            'business_id' => $this->salon['business']->id, 'service_order_id' => $this->order->id, 'catalogue_item_id' => $this->washId,
            'type' => 'service', 'name_snapshot' => 'Hair wash', 'unit_price_kobo' => 300_000, 'taxable_snapshot' => true,
            'status' => 'pending_approval', 'origin' => 'additional_request', 'requested_by' => $this->salon['supervisor']->id,
        ]);

        $this->as($this->salon['supervisor'])->postJson("api/v1/order-items/{$own->id}/approve")->assertStatus(403)->assertJsonPath('code', 'SEGREGATION_OF_DUTIES');

        $own->forceFill(['requested_by' => $this->salon['owner']->id])->save();
        $this->as($this->salon['owner'])->postJson("api/v1/order-items/{$own->id}/approve")->assertOk();
        $this->assertTrue(AuditEvent::query()->where('action', 'additional.approved')->sole()->sod_exempt);
    }

    public function test_the_requester_withdraws_before_a_decision_and_others_cannot(): void
    {
        $item = $this->pendingItem();

        $this->as($this->salon['bisi'])->postJson("api/v1/order-items/{$item->id}/withdraw")->assertStatus(403);
        $this->as($this->salon['ada'])->postJson("api/v1/order-items/{$item->id}/withdraw")->assertOk()->assertJsonPath('data.items.1.status', 'cancelled');
        $this->as($this->salon['supervisor'])->postJson("api/v1/order-items/{$item->id}/approve")->assertStatus(409);
    }

    public function test_work_on_a_pending_item_can_be_recorded_and_then_blocks_withdrawal(): void
    {
        $item = $this->pendingItem();

        $this->as($this->salon['bisi'])->withHeader('Idempotency-Key', $this->idempotencyKey())
            ->postJson("api/v1/order-items/{$item->id}/executions")->assertCreated();

        $this->as($this->salon['ada'])->postJson("api/v1/order-items/{$item->id}/withdraw")->assertStatus(409)->assertJsonPath('code', 'ITEM_RECORDED');
    }

    public function test_only_services_can_be_requested(): void
    {
        $hair = $this->product($this->salon['business'], 'Attachment hair', 450_000);

        $this->as($this->salon['ada'])->postJson("api/v1/orders/{$this->order->id}/additional-requests", ['catalogue_item_id' => $hair->id])
            ->assertStatus(422)->assertJsonPath('code', 'ITEM_NOT_ACTIVE');
    }

    private function request(User $actor, ?string $note = null): TestResponse
    {
        return $this->as($actor)->postJson("api/v1/orders/{$this->order->id}/additional-requests", array_filter(['catalogue_item_id' => $this->washId, 'note' => $note]));
    }

    private function pendingItem(): OrderItem
    {
        $id = $this->request($this->salon['ada'])->assertCreated()->json('data.items.1.id');

        return OrderItem::query()->withoutGlobalScopes()->findOrFail($id);
    }

    /**
     * @return list<string>
     */
    private function types(User $user): array
    {
        return InAppNotification::query()->withoutGlobalScopes()->where('user_id', $user->id)->pluck('type')->all();
    }
}
