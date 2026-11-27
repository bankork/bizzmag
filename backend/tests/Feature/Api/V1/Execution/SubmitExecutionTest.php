<?php

namespace Tests\Feature\Api\V1\Execution;

use App\Domain\Audit\AuditEvent;
use App\Domain\Business\Models\Business;
use App\Domain\Catalogue\Models\SplitMethod;
use App\Domain\Execution\Models\Execution;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\V1\ApiTestCase;
use Tests\Support\BuildsVisits;

/**
 * Recording a service (FR-EXE-002 to 008, FR-ATTR-001 to 008, SRS §6.3,
 * §14.1, §14.14 sequential part, §14.22).
 */
class SubmitExecutionTest extends ApiTestCase
{
    use BuildsVisits;

    public function test_three_stylists_on_one_braid_make_one_record_with_an_equal_split(): void
    {
        $salon = $this->salonWithStaff();
        $braid = $this->service($salon['business'], 'Knotless braids', 2_000_000);
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $braid->id]]);
        $item = $this->firstItem($order);

        $this->submit($salon['ada'], $item, [$salon['bisi'], $salon['chi']])
            ->assertCreated()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.allocation_method', 'equal')
            ->assertJsonPath('data.contributors.0.name', 'Ada')
            ->assertJsonPath('data.contributors.0.is_primary', true);

        $execution = Execution::query()->withoutGlobalScopes()->sole();
        $this->assertSame(['Ada' => 3334, 'Bisi' => 3333, 'Chi' => 3333], $this->sharesByName($execution));
        $this->assertSame($salon['ada']->id, $execution->submitted_by);

        $event = AuditEvent::query()->where('action', 'execution.submitted')->sole();
        $this->assertSame($order->id, $event->order_id);
        $this->assertSame($salon['ada']->id, $event->actor_id);
    }

    public function test_a_second_submission_is_refused_and_offered_join(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));
        $first = $this->record($salon['ada'], $item);

        $this->submit($salon['bisi'], $item)
            ->assertStatus(409)
            ->assertJsonPath('code', 'EXECUTION_EXISTS')
            ->assertJsonPath('data.execution_id', $first->id)
            ->assertJsonPath('data.can_join', true);

        $this->assertSame(1, Execution::query()->withoutGlobalScopes()->count());
    }

    public function test_someone_already_on_the_record_is_not_offered_join(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));
        $this->record($salon['ada'], $item, [$salon['bisi']]);

        $this->submit($salon['bisi'], $item)->assertStatus(409)->assertJsonPath('data.can_join', false);
    }

    public function test_joining_recalculates_the_split_with_the_service_method(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Wig install', 3_000_000, ['allocation_method' => SplitMethod::PrimaryAssistant, 'primary_share_bp' => 6000]);
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]));
        $execution = $this->record($salon['ada'], $item, [$salon['bisi']]);
        $this->assertSame(['Ada' => 6000, 'Bisi' => 4000], $this->sharesByName($execution));

        $this->as($salon['chi'])->postJson("api/v1/executions/{$execution->id}/join")->assertOk();

        $this->assertSame(['Ada' => 6000, 'Bisi' => 2000, 'Chi' => 2000], $this->sharesByName($execution->refresh()));
        $this->assertTrue(AuditEvent::query()->where('action', 'execution.joined')->where('actor_id', $salon['chi']->id)->exists());
    }

    public function test_the_split_comes_from_the_visit_not_from_a_later_catalogue_change(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Wig install', 3_000_000);
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]));

        app(CurrentBusiness::class)->within($salon['business']->id, fn () => $service->refresh()->forceFill(['allocation_method' => SplitMethod::PrimaryAssistant, 'primary_share_bp' => 8000])->save());

        $execution = $this->record($salon['ada'], $item, [$salon['bisi']]);
        $this->assertSame(['Ada' => 5000, 'Bisi' => 5000], $this->sharesByName($execution));
    }

    public function test_a_retry_with_the_same_key_records_once_and_replays_the_answer(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));
        $key = $this->idempotencyKey();

        $first = $this->submit($salon['ada'], $item, key: $key)->assertCreated()->assertHeader('Idempotent-Replayed', 'false');
        $again = $this->submit($salon['ada'], $item, key: $key)->assertCreated()->assertHeader('Idempotent-Replayed', 'true');

        $this->assertSame($first->json('data.id'), $again->json('data.id'));
        $this->assertSame(1, Execution::query()->withoutGlobalScopes()->count());

        $this->submit($salon['ada'], $item, [$salon['bisi']], key: $key)->assertStatus(422)->assertJsonPath('code', 'IDEMPOTENCY_MISMATCH');
    }

    public function test_submitting_without_an_idempotency_key_is_refused(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));

        $this->as($salon['ada'])->postJson("api/v1/order-items/{$item->id}/executions")->assertStatus(422)->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');
        $this->assertSame(0, Execution::query()->withoutGlobalScopes()->count());
    }

    public function test_someone_who_does_no_salon_work_must_choose_the_primary_and_is_not_credited(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['owner'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));

        $this->submit($salon['owner'], $item)->assertStatus(422)->assertJsonPath('code', 'ALLOCATION_INVALID');

        $this->submit($salon['owner'], $item, [$salon['bisi']], primary: $salon['ada'])->assertCreated();

        $execution = Execution::query()->withoutGlobalScopes()->sole();
        $this->assertSame(['Ada' => 5000, 'Bisi' => 5000], $this->sharesByName($execution));
        $this->assertSame($salon['owner']->id, $execution->submitted_by);
    }

    public function test_a_stylist_is_always_the_primary_on_work_they_record(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));

        $this->submit($salon['ada'], $item, [$salon['bisi']], primary: $salon['bisi'])->assertStatus(422)->assertJsonPath('code', 'ALLOCATION_INVALID');
    }

    public function test_disabled_staff_and_staff_of_another_salon_cannot_be_added(): void
    {
        $salon = $this->salonWithStaff();
        $other = $this->installSalon('Other Salon', 'owner@other.test');
        $outsider = $this->staffMember($other['business'], SystemRole::Employee);
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));
        $salon['chi']->forceFill(['is_active' => false])->save();

        $this->submit($salon['ada'], $item, [$salon['chi']])->assertStatus(422)->assertJsonPath('code', 'STAFF_NOT_ELIGIBLE');
        $this->as($salon['ada'])->withHeader('Idempotency-Key', $this->idempotencyKey())
            ->postJson("api/v1/order-items/{$item->id}/executions", ['contributor_employee_ids' => [$this->profileOf($outsider)->id]])
            ->assertStatus(422)->assertJsonPath('code', 'STAFF_NOT_ELIGIBLE');

        $this->assertSame(0, Execution::query()->withoutGlobalScopes()->count());
    }

    public function test_a_disabled_employee_stays_on_their_past_records(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]);
        $this->record($salon['ada'], $this->firstItem($order), [$salon['chi']]);
        $salon['chi']->forceFill(['is_active' => false])->save();

        $this->as($salon['cashier'])->getJson("api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.items.0.work.contributors.1.name', 'Chi');
    }

    public function test_products_are_not_recorded_as_work(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->product($salon['business'], 'Attachment hair', 450_000)->id]]);

        $this->submit($salon['ada'], $this->firstItem($order))->assertStatus(409)->assertJsonPath('code', 'INVALID_TRANSITION');
    }

    public function test_work_cannot_be_recorded_on_a_cancelled_item_or_visit(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Cornrows', 800_000);
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id], ['catalogue_item_id' => $service->id]]);
        [$first, $second] = OrderItem::query()->withoutGlobalScopes()->where('service_order_id', $order->id)->orderBy('id')->get()->all();

        $this->as($salon['cashier'])->postJson("api/v1/order-items/{$first->id}/cancel", ['reason' => 'Customer changed her mind'])->assertOk();
        $this->submit($salon['ada'], $first)->assertStatus(409)->assertJsonPath('code', 'INVALID_TRANSITION');

        $this->as($salon['supervisor'])->postJson("api/v1/orders/{$order->id}/cancel", ['reason' => 'Customer left'])->assertOk();
        $this->submit($salon['ada'], $second)->assertStatus(409);
    }

    public function test_after_finalisation_work_is_accepted_within_the_late_window_and_marked_late(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->itemOnFinalisedVisit($salon, hoursAgo: 2);

        $this->submit($salon['ada'], $item)->assertCreated()->assertJsonPath('data.late', true);
    }

    public function test_after_the_late_window_only_execution_submit_late_may_record(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->itemOnFinalisedVisit($salon, hoursAgo: 49);

        $this->submit($salon['ada'], $item)->assertStatus(403)->assertJsonPath('code', 'LATE_WINDOW_CLOSED');
        $this->submit($salon['supervisor'], $item)->assertCreated()->assertJsonPath('data.late', true);
    }

    public function test_the_database_itself_refuses_a_second_active_record_for_an_item(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));
        $first = $this->record($salon['ada'], $item);

        $this->expectException(UniqueConstraintViolationException::class);

        DB::table('executions')->insert([
            'business_id' => $first->business_id, 'order_item_id' => $item->id, 'status' => 'disputed',
            'submitted_by' => $salon['bisi']->id, 'submitted_at' => now(), 'allocation_method' => 'equal',
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_replaced_and_cancelled_records_do_not_block_a_new_one(): void
    {
        $salon = $this->salonWithStaff();
        $item = $this->firstItem($this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]));
        $first = $this->record($salon['ada'], $item);

        $this->as($salon['supervisor'])->postJson("api/v1/executions/{$first->id}/cancel", ['reason' => 'Recorded on the wrong visit'])->assertOk();
        $this->submit($salon['bisi'], $item)->assertCreated();

        $this->assertSame(['cancelled', 'confirmed'], Execution::query()->withoutGlobalScopes()->orderBy('id')->pluck('status')->map->value->all());
    }

    /**
     * @param  array{business: Business, owner: User}  $salon
     */
    private function itemOnFinalisedVisit(array $salon, int $hoursAgo): OrderItem
    {
        $service = $this->service($salon['business'], 'Cornrows', 800_000);

        return app(CurrentBusiness::class)->within($salon['business']->id, function () use ($salon, $service, $hoursAgo): OrderItem {
            $order = ServiceOrder::factory()->finalised($hoursAgo, 48)->create(['created_by' => $salon['owner']->id]);

            return OrderItem::query()->create([
                'service_order_id' => $order->id, 'catalogue_item_id' => $service->id, 'type' => 'service',
                'name_snapshot' => 'Cornrows', 'unit_price_kobo' => 800_000, 'taxable_snapshot' => true, 'quantity' => 1,
                'allocation_method_snapshot' => 'equal', 'status' => 'requested', 'origin' => 'order', 'requested_by' => $salon['owner']->id,
            ]);
        });
    }

    /**
     * @param  list<User>  $colleagues
     */
    private function submit(User $actor, OrderItem $item, array $colleagues = [], ?User $primary = null, ?string $key = null): TestResponse
    {
        return $this->as($actor)
            ->withHeader('Idempotency-Key', $key ?? $this->idempotencyKey())
            ->postJson("api/v1/order-items/{$item->id}/executions", array_filter([
                'contributor_employee_ids' => array_map(fn (User $colleague) => $this->profileOf($colleague)->id, $colleagues),
                'primary_employee_id' => $primary === null ? null : $this->profileOf($primary)->id,
            ], fn ($value) => $value !== null && $value !== []));
    }
}
