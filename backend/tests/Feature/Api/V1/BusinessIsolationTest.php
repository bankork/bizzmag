<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Business\Models\BusinessSetting;
use App\Domain\Business\Models\TaxSetting;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Shared\Exceptions\BusinessScopeMissing;
use App\Domain\Shared\Tenancy\CurrentBusiness;

/**
 * FR-IAM-007 and SEC-003: with two salons in one database, nobody in one
 * can see or change anything in the other, by list or by ID.
 */
class BusinessIsolationTest extends ApiTestCase
{
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
