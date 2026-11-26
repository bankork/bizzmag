<?php

namespace Tests\Feature\Api\V1\Catalogue;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Tests\Feature\Api\V1\ApiTestCase;
use Tests\Support\BuildsCatalogue;

/**
 * BR-041 and SRS §14.23: employees get the services they can record, with
 * no prices at all. Staff who see visits with prices get them.
 */
class EmployeeCatalogueViewTest extends ApiTestCase
{
    use BuildsCatalogue;

    public function test_an_employee_receives_no_price_fields_at_any_level(): void
    {
        $salon = $this->installSalon();
        $knotless = $this->serviceWithVariants($salon['business'], 'Knotless braids', ['Small' => 5_500_000, 'Medium' => 4_000_000]);
        $this->service($salon['business'], 'Silk press', 1_800_000);
        $this->signIn($this->staffMember($salon['business'], SystemRole::Employee));

        $list = $this->getJson('api/v1/catalogue/items')->assertOk();
        $detail = $this->getJson("api/v1/catalogue/items/{$knotless->id}")->assertOk();

        $this->assertCount(2, $list->json('data'));
        foreach ([...$list->json('data'), ...$list->json('data.0.variants'), $detail->json('data'), ...$detail->json('data.variants')] as $item) {
            $this->assertArrayNotHasKey('price_kobo', $item);
        }
        $this->assertStringNotContainsString('5500000', $list->getContent());
        $this->assertStringNotContainsString('1800000', $list->getContent());
    }

    public function test_a_cashier_receives_prices(): void
    {
        $salon = $this->installSalon();
        $this->service($salon['business'], 'Silk press', 1_800_000);

        $this->signIn($this->staffMember($salon['business'], SystemRole::Cashier))->getJson('api/v1/catalogue/items')
            ->assertOk()
            ->assertJsonPath('data.0.price_kobo', 1_800_000);
    }

    public function test_a_custom_role_without_visit_prices_gets_no_prices(): void
    {
        $salon = $this->installSalon();
        $this->service($salon['business'], 'Silk press', 1_800_000);
        $user = $this->staffMember($salon['business'], SystemRole::Employee);
        app(CurrentBusiness::class)->within($salon['business']->id, function () use ($salon, $user): void {
            $role = Role::query()->create(['name' => 'Receptionist', 'guard_name' => 'web', 'business_id' => $salon['business']->id]);
            $role->givePermissionTo(['customer.view', 'order.create']);
            $user->syncRoles([$role]);
        });

        $this->signIn($user->refresh())->getJson('api/v1/catalogue/items')->assertOk()->assertJsonMissingPath('data.0.price_kobo');
    }

    public function test_an_employee_sees_only_what_can_be_chosen_for_a_visit(): void
    {
        $salon = $this->installSalon();
        $knotless = $this->serviceWithVariants($salon['business'], 'Knotless braids', ['Small' => 5_500_000, 'Jumbo' => 2_000_000]);
        $retired = $this->service($salon['business'], 'Retired service', 100_000, ['is_active' => false]);
        $this->serviceWithVariants($salon['business'], 'Empty service', []);
        app(CurrentBusiness::class)->within($salon['business']->id, fn () => $knotless->variants->firstWhere('name', 'Jumbo')?->forceFill(['is_active' => false])->save());
        $this->signIn($this->staffMember($salon['business'], SystemRole::Employee));

        $list = $this->getJson('api/v1/catalogue/items?filter[active]=0')->assertOk();

        $this->assertSame(['Knotless braids'], array_column($list->json('data'), 'name'));
        $this->assertSame(['Small'], array_column($list->json('data.0.variants'), 'name'));
        $this->getJson("api/v1/catalogue/items/{$retired->id}")->assertNotFound();
        $this->assertSame(['Small'], array_column($this->getJson("api/v1/catalogue/items/{$knotless->id}")->json('data.variants'), 'name'));
    }
}
