<?php

namespace Tests\Feature\Api\V1\Orders;

use App\Domain\Audit\AuditEvent;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Catalogue\Models\SplitMethod;
use App\Domain\Customers\Models\Customer;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Illuminate\Support\Carbon;
use Tests\Feature\Api\V1\ApiTestCase;
use Tests\Support\BuildsVisits;

/**
 * Opening and changing visits (SRS §4.5, §6.1, §6.2, FR-CUS-003 to 006,
 * §14.18, §14.19).
 */
class OrderTest extends ApiTestCase
{
    use BuildsVisits;

    public function test_the_desk_opens_a_visit_for_a_customer_with_variants_products_and_assignments(): void
    {
        $salon = $this->salonWithStaff();
        $braids = $this->serviceWithVariants($salon['business'], 'Knotless braids', ['Small' => 5_500_000, 'Medium' => 4_000_000], ['allocation_method' => SplitMethod::PrimaryAssistant, 'primary_share_bp' => 6000]);
        $hair = $this->product($salon['business'], 'Attachment hair pack', 450_000);
        $medium = $braids->variants->firstWhere('name', 'Medium');
        $customer = app(CurrentBusiness::class)->within($salon['business']->id, fn () => Customer::factory()->create(['name' => 'Funke Adeyemi']));
        Carbon::setTestNow('2026-11-27 09:15:00');

        $response = $this->as($salon['cashier'])->postJson('api/v1/orders', [
            'customer_id' => $customer->id,
            'items' => [
                ['catalogue_item_id' => $medium->id, 'employee_ids' => [$this->profileOf($salon['ada'])->id]],
                ['catalogue_item_id' => $hair->id, 'quantity' => 3],
            ],
        ])->assertCreated();

        $response->assertJsonPath('data.reference', 'V-261127-001')
            ->assertJsonPath('data.customer_name', 'Funke Adeyemi')
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.items.0.name', 'Knotless braids, Medium')
            ->assertJsonPath('data.items.0.unit_price_kobo', 4_000_000)
            ->assertJsonPath('data.items.0.assigned.0.name', 'Ada')
            ->assertJsonPath('data.items.1.quantity', 3)
            ->assertJsonPath('data.items.1.line_value_kobo', 1_350_000)
            ->assertJsonPath('data.subtotal_kobo', 5_350_000)
            ->assertJsonPath('data.progress.services', 1);

        $item = OrderItem::query()->withoutGlobalScopes()->orderBy('id')->firstOrFail();
        $this->assertSame(SplitMethod::PrimaryAssistant, $item->allocation_method_snapshot);
        $this->assertSame(6000, $item->primary_share_bp_snapshot);
        $this->assertSame(['order.assigned'], InAppNotification::query()->withoutGlobalScopes()->where('user_id', $salon['ada']->id)->pluck('type')->all());
        $this->assertSame('2026-11-27', ServiceOrder::query()->withoutGlobalScopes()->sole()->opened_business_day);
        $this->assertTrue(AuditEvent::query()->where('action', 'order.created')->where('actor_id', $salon['cashier']->id)->exists());
    }

    public function test_references_run_on_per_day_and_restart_the_next_day(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Cornrows', 800_000);
        Carbon::setTestNow('2026-11-27 08:00:00');

        $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]);
        $second = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]);
        Carbon::setTestNow('2026-11-28 08:00:00');
        $nextDay = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]);

        $this->assertSame('V-261127-002', $second->reference);
        $this->assertSame('V-261128-001', $nextDay->reference);
    }

    public function test_a_guest_visit_needs_no_name_or_phone_and_can_be_linked_to_a_customer_later(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Cornrows', 800_000);
        $customer = app(CurrentBusiness::class)->within($salon['business']->id, fn () => Customer::factory()->create(['name' => 'Ngozi Okeke']));

        $order = $this->as($salon['cashier'])->postJson('api/v1/orders', ['items' => [['catalogue_item_id' => $service->id]]])
            ->assertCreated()->assertJsonPath('data.customer_name', 'Guest')->assertJsonPath('data.is_guest', true)->json('data.id');

        $this->as($salon['cashier'])->postJson("api/v1/orders/{$order}/customer", ['customer_id' => $customer->id])
            ->assertOk()->assertJsonPath('data.customer_name', 'Ngozi Okeke')->assertJsonPath('data.is_guest', false);

        $this->as($salon['cashier'])->postJson("api/v1/orders/{$order}/customer", ['customer_id' => $customer->id])->assertStatus(409);
        $this->assertTrue(AuditEvent::query()->where('action', 'order.customer_associated')->exists());
    }

    public function test_a_visit_keeps_the_price_it_was_opened_with(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Wig install', 2_000_000);
        $before = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]);

        $this->changePrice($service, 2_500_000);
        $after = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]);

        $this->as($salon['cashier'])->getJson("api/v1/orders/{$before->id}")->assertJsonPath('data.items.0.unit_price_kobo', 2_000_000);
        $this->as($salon['cashier'])->getJson("api/v1/orders/{$after->id}")->assertJsonPath('data.items.0.unit_price_kobo', 2_500_000);
    }

    public function test_inactive_items_parents_with_variants_and_wrong_quantities_are_refused(): void
    {
        $salon = $this->salonWithStaff();
        $braids = $this->serviceWithVariants($salon['business'], 'Knotless braids', ['Small' => 5_500_000]);
        $inactive = $this->service($salon['business'], 'Old style', 500_000, ['is_active' => false]);
        $once = $this->service($salon['business'], 'Cornrows', 800_000);

        foreach ([$braids->id, $inactive->id] as $itemId) {
            $this->as($salon['cashier'])->postJson('api/v1/orders', ['items' => [['catalogue_item_id' => $itemId]]])
                ->assertStatus(422)->assertJsonPath('code', 'ITEM_NOT_ACTIVE');
        }

        $this->as($salon['cashier'])->postJson('api/v1/orders', ['items' => [['catalogue_item_id' => $once->id, 'quantity' => 2]]])
            ->assertStatus(422)->assertJsonPath('code', 'QUANTITY_NOT_ALLOWED');
        $this->as($salon['cashier'])->postJson('api/v1/orders', ['items' => []])->assertStatus(422)->assertJsonValidationErrors('items');

        $this->assertSame(0, ServiceOrder::query()->withoutGlobalScopes()->count());
    }

    public function test_a_removed_customer_cannot_be_given_a_new_visit(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Cornrows', 800_000);
        $customer = app(CurrentBusiness::class)->within($salon['business']->id, fn () => Customer::factory()->create(['name' => Customer::REMOVED_NAME, 'anonymised_at' => now()]));

        $this->as($salon['cashier'])->postJson('api/v1/orders', ['customer_id' => $customer->id, 'items' => [['catalogue_item_id' => $service->id]]])
            ->assertStatus(409)->assertJsonPath('code', 'CUSTOMER_REMOVED');
    }

    public function test_assignments_accept_only_staff_who_do_salon_work(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]);
        $item = $this->firstItem($order);
        $salon['chi']->forceFill(['is_active' => false])->save();

        $this->as($salon['cashier'])->putJson("api/v1/order-items/{$item->id}/assignments", ['employee_ids' => [$this->profileOf($salon['chi'])->id]])
            ->assertStatus(422)->assertJsonPath('code', 'STAFF_NOT_ELIGIBLE');

        $this->as($salon['cashier'])->putJson("api/v1/order-items/{$item->id}/assignments", ['employee_ids' => [$this->profileOf($salon['ada'])->id, $this->profileOf($salon['bisi'])->id]])
            ->assertOk()->assertJsonCount(2, 'data.items.0.assigned');
        $this->as($salon['cashier'])->putJson("api/v1/order-items/{$item->id}/assignments", ['employee_ids' => []])
            ->assertOk()->assertJsonCount(0, 'data.items.0.assigned');
    }

    public function test_the_desk_adds_an_item_and_removes_one_nobody_has_done(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]);
        $wash = $this->service($salon['business'], 'Wash and set', 300_000);

        $this->as($salon['cashier'])->postJson("api/v1/orders/{$order->id}/items", ['catalogue_item_id' => $wash->id])
            ->assertCreated()->assertJsonPath('data.items.1.origin', 'desk_add')->assertJsonPath('data.subtotal_kobo', 1_100_000);

        $this->as($salon['cashier'])->postJson("api/v1/order-items/{$this->firstItem($order)->id}/cancel", ['reason' => 'Customer changed her mind'])
            ->assertOk()->assertJsonPath('data.items.0.status', 'cancelled')->assertJsonPath('data.subtotal_kobo', 300_000);

        $this->as($salon['cashier'])->postJson("api/v1/order-items/{$this->firstItem($order)->id}/cancel", [])->assertStatus(422)->assertJsonValidationErrors('reason');
    }

    public function test_a_supervisor_changes_a_price_with_a_reason_and_the_original_stays(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]);
        $item = $this->firstItem($order);

        $this->as($salon['supervisor'])->postJson("api/v1/order-items/{$item->id}/price-override", ['price_kobo' => 700_000])
            ->assertStatus(422)->assertJsonValidationErrors('reason');

        $this->as($salon['supervisor'])->postJson("api/v1/order-items/{$item->id}/price-override", ['price_kobo' => 700_000, 'reason' => 'Short length, agreed with owner'])
            ->assertOk()
            ->assertJsonPath('data.items.0.unit_price_kobo', 800_000)
            ->assertJsonPath('data.items.0.override_price_kobo', 700_000)
            ->assertJsonPath('data.subtotal_kobo', 700_000);

        $this->as($salon['cashier'])->postJson("api/v1/order-items/{$item->id}/price-override", ['price_kobo' => 1, 'reason' => 'Trying'])->assertStatus(403);
        $this->assertSame('Short length, agreed with owner', AuditEvent::query()->where('action', 'order.item_price_overridden')->sole()->reason);
    }

    public function test_a_cancelled_visit_rejects_ordinary_changes(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Cornrows', 800_000);
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]);
        $this->as($salon['supervisor'])->postJson("api/v1/orders/{$order->id}/cancel", ['reason' => 'Customer left'])->assertOk();

        $this->as($salon['cashier'])->postJson("api/v1/orders/{$order->id}/items", ['catalogue_item_id' => $service->id])->assertStatus(409)->assertJsonPath('code', 'ORDER_NOT_OPEN');
        $this->as($salon['supervisor'])->postJson("api/v1/orders/{$order->id}/cancel", ['reason' => 'Again'])->assertStatus(409);
    }

    public function test_the_board_lists_open_visits_oldest_first_and_searches_by_reference_or_name(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Cornrows', 800_000);
        Carbon::setTestNow('2026-11-27 08:00:00');
        $first = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]], ['guest_name' => 'Amaka']);
        Carbon::setTestNow('2026-11-27 09:00:00');
        $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]], ['guest_name' => 'Zainab']);
        $cancelled = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]], ['guest_name' => 'Yemi']);
        $this->as($salon['supervisor'])->postJson("api/v1/orders/{$cancelled->id}/cancel", ['reason' => 'Left'])->assertOk();

        $this->as($salon['cashier'])->getJson('api/v1/orders')->assertOk()->assertJsonCount(2, 'data')->assertJsonPath('data.0.id', $first->id);
        $this->as($salon['cashier'])->getJson('api/v1/orders?filter[q]=zain')->assertJsonCount(1, 'data')->assertJsonPath('data.0.customer_name', 'Zainab');
        $this->as($salon['cashier'])->getJson('api/v1/orders?filter[q]=V-261127-001')->assertJsonCount(1, 'data');
        $this->as($salon['cashier'])->getJson('api/v1/orders?filter[status]=cancelled')->assertJsonCount(1, 'data');
    }

    public function test_a_customer_page_lists_their_visits(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Cornrows', 800_000);
        $customer = app(CurrentBusiness::class)->within($salon['business']->id, fn () => Customer::factory()->create());
        $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]], ['customer_id' => $customer->id]);

        $this->as($salon['cashier'])->getJson("api/v1/customers/{$customer->id}/orders")
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.items.0', 'Cornrows')->assertJsonPath('data.0.subtotal_kobo', 800_000);
    }

    public function test_catalogue_items_are_unchanged_by_visits(): void
    {
        $salon = $this->salonWithStaff();
        $service = $this->service($salon['business'], 'Cornrows', 800_000);
        $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $service->id]]);

        $this->assertSame(1, CatalogueItem::query()->withoutGlobalScopes()->findOrFail($service->id)->version);
    }
}
