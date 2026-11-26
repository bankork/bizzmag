<?php

namespace Tests\Feature\Api\V1\Business;

use App\Domain\Audit\AuditEvent;
use Tests\Feature\Api\V1\ApiTestCase;

/**
 * FR-TAX-001 and FR-TAX-008: one tax, off on a new installation, switched on
 * and off by the owner, and every change audited.
 */
class TaxSettingsTest extends ApiTestCase
{
    public function test_tax_is_off_on_a_new_installation(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->getJson('api/v1/settings/tax')
            ->assertOk()
            ->assertJsonPath('data.enabled', false)
            ->assertJsonPath('data.name', 'VAT')
            ->assertJsonPath('data.rate_bp', 750)
            ->assertJsonPath('data.mode', 'exclusive')
            ->assertJsonPath('data.version', 1);
    }

    public function test_switching_tax_on_and_off_is_audited_with_before_and_after(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);

        $this->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'inclusive'])
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.mode', 'inclusive')
            ->assertJsonPath('data.version', 2);
        $this->patchJson('api/v1/settings/tax', ['version' => 2, 'enabled' => false, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'inclusive'])
            ->assertOk()
            ->assertJsonPath('data.enabled', false);

        $events = AuditEvent::query()->where('action', 'tax.updated')->orderBy('id')->get();
        $this->assertCount(2, $events);
        $this->assertEquals(['enabled' => false, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'exclusive'], $events[0]->before);
        $this->assertEquals(['enabled' => true, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'inclusive'], $events[0]->after);
        $this->assertFalse($events[1]->after['enabled']);
        $this->assertSame($salon['owner']->id, $events[1]->actor_id);
    }

    public function test_a_tax_change_based_on_an_old_version_is_refused(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner'])->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'exclusive'])->assertOk();

        $this->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => false, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'exclusive'])
            ->assertStatus(409)
            ->assertJsonPath('code', 'STALE');
    }

    public function test_tax_cannot_be_switched_on_at_zero_percent_or_above_one_hundred(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);

        $this->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 0, 'mode' => 'exclusive'])
            ->assertStatus(422)
            ->assertJsonPath('errors.rate_bp.0', 'Enter a tax rate above 0%, or switch tax off.');
        $this->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 10001, 'mode' => 'exclusive'])
            ->assertStatus(422)
            ->assertJsonPath('errors.rate_bp.0', 'The tax rate cannot be more than 100%.');
        $this->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'compound'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['mode'], 'errors');
    }

    public function test_there_is_only_ever_one_tax_per_business(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);

        $this->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'exclusive'])->assertOk();
        $this->patchJson('api/v1/settings/tax', ['version' => 2, 'enabled' => true, 'name' => 'Sales tax', 'rate_bp' => 500, 'mode' => 'exclusive'])->assertOk();

        $this->assertDatabaseCount('tax_settings', 1);
    }
}
