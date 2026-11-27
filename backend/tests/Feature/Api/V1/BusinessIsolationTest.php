<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Business\Models\BusinessSetting;
use App\Domain\Business\Models\TaxSetting;
use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Customers\Models\Customer;
use App\Domain\Execution\Models\Execution;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Shared\Exceptions\BusinessScopeMissing;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Tests\Support\BuildsVisits;

/**
 * FR-IAM-007 and SEC-003: with two salons in one database, nobody in one
 * can see or change anything in the other, by list or by ID.
 */
class BusinessIsolationTest extends ApiTestCase
{
    use BuildsVisits;

    public function test_the_staff_list_shows_only_this_salon(): void
    {
        $adunni = $this->installSalon('Adunni Hair & Beauty Studio', 'owner@adunni.test');
        $ivie = $this->installSalon('Ivie\'s Place, Benin', 'owner@ivie.test');
        $this->staffMember($ivie['business'], SystemRole::Employee, ['name' => 'Osaro Ighodaro']);

        $names = array_column($this->signIn($adunni['owner'])->getJson('api/v1/users')->assertOk()->json('data'), 'name');

        $this->assertSame(['Salon Owner'], $names);
    }

    public function test_a_user_from_another_salon_is_not_found_by_id(): void
    {
        $adunni = $this->installSalon('Adunni Hair & Beauty Studio', 'owner@adunni.test');
        $ivie = $this->installSalon('Ivie\'s Place, Benin', 'owner@ivie.test');
        $osaro = $this->staffMember($ivie['business'], SystemRole::Employee);
        $this->signIn($adunni['owner']);

        $this->getJson("api/v1/users/{$osaro->id}")->assertNotFound();
        $this->patchJson("api/v1/users/{$osaro->id}", ['name' => 'Taken over'])->assertNotFound();
        $this->postJson("api/v1/users/{$osaro->id}/disable")->assertNotFound();
        $this->postJson("api/v1/users/{$osaro->id}/reset-password", ['password' => 'Temp-pass-2026'])->assertNotFound();

        $this->assertTrue($osaro->refresh()->is_active);
        $this->assertNotSame('Taken over', $osaro->name);
    }

    public function test_roles_from_another_salon_cannot_be_seen_edited_or_assigned(): void
    {
        $adunni = $this->installSalon('Adunni Hair & Beauty Studio', 'owner@adunni.test');
        $ivie = $this->installSalon('Ivie\'s Place, Benin', 'owner@ivie.test');
        $theirCashier = $this->systemRole($ivie['business'], SystemRole::Cashier);
        $employee = $this->staffMember($adunni['business'], SystemRole::Employee);
        $this->signIn($adunni['owner']);

        $roleIds = array_column($this->getJson('api/v1/roles')->json('data'), 'id');
        $this->assertNotContains($theirCashier->id, $roleIds);
        $this->assertCount(4, $roleIds);

        $this->patchJson("api/v1/roles/{$theirCashier->id}", ['discount_cap_bp' => 10000])->assertNotFound();
        $this->putJson("api/v1/users/{$employee->id}/roles", ['role_ids' => [$theirCashier->id]])->assertStatus(422);
        $this->assertSame(1000, $theirCashier->refresh()->discount_cap_bp);
    }

    public function test_settings_tax_and_history_belong_to_each_salon(): void
    {
        $adunni = $this->installSalon('Adunni Hair & Beauty Studio', 'owner@adunni.test');
        $ivie = $this->installSalon('Ivie\'s Place, Benin', 'owner@ivie.test');

        $this->signIn($ivie['owner'])->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'exclusive'])->assertOk();
        $this->patchJson('api/v1/settings', ['version' => 1, 'windows' => ['late_submission_hours' => 12]])->assertOk();

        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->signIn($adunni['owner']);

        $this->getJson('api/v1/settings/tax')->assertJsonPath('data.enabled', false);
        $this->getJson('api/v1/settings')->assertJsonPath('data.profile.name', 'Adunni Hair & Beauty Studio')->assertJsonPath('data.windows.late_submission_hours.value', 48);
        $this->assertSame(['business.installed'], array_column($this->getJson('api/v1/settings/history')->json('data'), 'action'));
    }

    public function test_catalogue_items_and_categories_belong_to_each_salon(): void
    {
        $adunni = $this->installSalon('Adunni Hair & Beauty Studio', 'owner@adunni.test');
        $ivie = $this->installSalon('Ivie\'s Place, Benin', 'owner@ivie.test');
        $theirs = app(CurrentBusiness::class)->within($ivie['business']->id, function (): CatalogueItem {
            $category = CatalogueCategory::factory()->create(['name' => 'Braids']);

            return CatalogueItem::factory()->create(['category_id' => $category->id, 'name' => 'Ivie knotless', 'price_kobo' => 3_000_000]);
        });
        $this->signIn($adunni['owner']);

        $this->assertSame([], $this->getJson('api/v1/catalogue/items')->json('data'));
        $this->assertSame([], $this->getJson('api/v1/catalogue/categories')->json('data'));
        $this->getJson("api/v1/catalogue/items/{$theirs->id}")->assertNotFound();
        $this->getJson("api/v1/catalogue/items/{$theirs->id}/history")->assertNotFound();
        $this->patchJson("api/v1/catalogue/items/{$theirs->id}", ['version' => 1, 'price_kobo' => 1])->assertNotFound();
        $this->postJson("api/v1/catalogue/items/{$theirs->id}/deactivate")->assertNotFound();
        $this->patchJson("api/v1/catalogue/categories/{$theirs->category_id}", ['version' => 1, 'name' => 'Taken'])->assertNotFound();
        $this->postJson('api/v1/catalogue/items', ['category_id' => $theirs->category_id, 'name' => 'Sneaky', 'price_kobo' => 1, 'allocation_method' => 'equal'])->assertStatus(422);
        $this->postJson('api/v1/catalogue/items', ['parent_id' => $theirs->id, 'name' => 'Sneaky', 'price_kobo' => 1])->assertStatus(422);
        $this->postJson('api/v1/catalogue/categories/reorder', ['type' => 'service', 'ids' => [$theirs->category_id]])->assertStatus(422);

        $this->assertSame(3_000_000, CatalogueItem::query()->withoutGlobalScopes()->findOrFail($theirs->id)->price_kobo);
        $this->assertSame(1, CatalogueItem::query()->withoutGlobalScopes()->count());
    }

    public function test_customers_belong_to_each_salon_and_their_numbers_do_not_trigger_the_warning(): void
    {
        $adunni = $this->installSalon('Adunni Hair & Beauty Studio', 'owner@adunni.test');
        $ivie = $this->installSalon('Ivie\'s Place, Benin', 'owner@ivie.test');
        $theirs = app(CurrentBusiness::class)->within($ivie['business']->id, fn () => Customer::factory()->create(['name' => 'Osas Ehigie', 'phone_e164' => '+2348030001234']));
        $this->signIn($adunni['owner']);

        $this->assertSame([], $this->getJson('api/v1/customers?filter[q]=Osas')->json('data'));
        $this->assertSame([], $this->getJson('api/v1/customers?filter[q]=08030001234')->json('data'));
        $this->getJson("api/v1/customers/{$theirs->id}")->assertNotFound();
        $this->patchJson("api/v1/customers/{$theirs->id}", ['version' => 1, 'name' => 'Taken'])->assertNotFound();
        $this->postJson("api/v1/customers/{$theirs->id}/anonymise", ['confirm' => 'REMOVE'])->assertNotFound();
        $this->postJson('api/v1/customers', ['name' => 'Funke Adeyemi', 'phone' => '08030001234'])->assertCreated();

        $this->assertSame('Osas Ehigie', Customer::query()->withoutGlobalScopes()->findOrFail($theirs->id)->name);
    }

    public function test_visits_items_and_records_of_another_salon_cannot_be_seen_or_changed(): void
    {
        $adunni = $this->installSalon('Adunni Hair & Beauty Studio', 'owner@adunni.test');
        $ivie = $this->installSalon('Ivie\'s Place, Benin', 'owner@ivie.test');
        $iviesStylist = $this->staffMember($ivie['business'], SystemRole::Employee);
        $theirService = $this->service($ivie['business'], 'Ivie knotless', 3_000_000);
        $theirOrder = $this->openVisit($ivie['business'], $ivie['owner'], [['catalogue_item_id' => $theirService->id]]);
        $theirItem = $this->firstItem($theirOrder);
        $theirWork = $this->record($iviesStylist, $theirItem);
        $ours = $this->service($adunni['business'], 'Cornrows', 800_000);
        $this->signIn($adunni['owner']);

        $this->assertSame([], $this->getJson('api/v1/orders?filter[status]=all')->json('data'));
        $this->assertSame([], $this->getJson('api/v1/approvals')->json('data'));
        $this->getJson("api/v1/orders/{$theirOrder->id}")->assertNotFound();
        $this->postJson("api/v1/orders/{$theirOrder->id}/items", ['catalogue_item_id' => $ours->id])->assertNotFound();
        $this->postJson("api/v1/orders/{$theirOrder->id}/cancel", ['reason' => 'Taken over'])->assertNotFound();
        $this->postJson("api/v1/order-items/{$theirItem->id}/cancel", ['reason' => 'Taken over'])->assertNotFound();
        $this->postJson("api/v1/order-items/{$theirItem->id}/price-override", ['price_kobo' => 1, 'reason' => 'Taken over'])->assertNotFound();
        $this->withHeader('Idempotency-Key', 'isolation-0001')->postJson("api/v1/order-items/{$theirItem->id}/executions", ['primary_employee_id' => $this->profileOf($iviesStylist)->id])->assertNotFound();
        $this->postJson("api/v1/executions/{$theirWork->id}/cancel", ['reason' => 'Taken over'])->assertNotFound();
        $this->postJson("api/v1/executions/{$theirWork->id}/dispute", ['reason' => 'Taken over'])->assertNotFound();

        $this->postJson('api/v1/orders', ['items' => [['catalogue_item_id' => $theirService->id]]])->assertStatus(422)->assertJsonPath('code', 'ITEM_NOT_ACTIVE');
        $this->postJson('api/v1/orders', ['items' => [['catalogue_item_id' => $ours->id, 'employee_ids' => [$this->profileOf($iviesStylist)->id]]]])
            ->assertStatus(422)->assertJsonPath('code', 'STAFF_NOT_ELIGIBLE');
        $this->assertSame([], array_column($this->getJson('api/v1/staff-directory')->json('data'), 'name'));

        $this->assertSame('open', ServiceOrder::query()->withoutGlobalScopes()->findOrFail($theirOrder->id)->status->value);
        $this->assertSame('confirmed', Execution::query()->withoutGlobalScopes()->findOrFail($theirWork->id)->status->value);
    }

    public function test_notifications_belong_to_their_person_and_salon(): void
    {
        $adunni = $this->installSalon('Adunni Hair & Beauty Studio', 'owner@adunni.test');
        $ivie = $this->installSalon('Ivie\'s Place, Benin', 'owner@ivie.test');
        $stylist = $this->staffMember($ivie['business'], SystemRole::Employee);
        $order = $this->openVisit($ivie['business'], $ivie['owner'], [['catalogue_item_id' => $this->service($ivie['business'], 'Ivie knotless', 3_000_000)->id, 'employee_ids' => [$this->profileOf($stylist)->id]]]);

        $this->assertSame(1, InAppNotification::query()->withoutGlobalScopes()->where('business_id', $ivie['business']->id)->count());
        $this->signIn($adunni['owner']);
        $this->assertSame([], $this->getJson('api/v1/notifications')->json('data'));
        $this->postJson('api/v1/notifications/read', ['all' => true])->assertJsonPath('data.marked', 0);
        $this->assertNotNull($order->id);
    }

    public function test_business_owned_models_return_nothing_without_a_current_business(): void
    {
        $adunni = $this->installSalon();
        app(CurrentBusiness::class)->set(null);

        $this->assertSame(0, TaxSetting::query()->count());
        $this->assertSame(0, BusinessSetting::query()->count());
        $this->assertSame(1, app(CurrentBusiness::class)->within($adunni['business']->id, fn () => TaxSetting::query()->count()));
    }

    public function test_creating_business_owned_data_without_a_current_business_is_refused(): void
    {
        $this->expectException(BusinessScopeMissing::class);

        EmployeeProfile::factory()->create();
    }
}
