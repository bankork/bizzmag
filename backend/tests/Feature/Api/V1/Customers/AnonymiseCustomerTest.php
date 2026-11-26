<?php

namespace Tests\Feature\Api\V1\Customers;

use App\Domain\Audit\AuditEvent;
use App\Domain\Customers\Models\Customer;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Tests\Feature\Api\V1\ApiTestCase;

/**
 * SEC-021: on request, the owner removes a customer's personal data. The
 * record stays for the financial history; nothing personal is left behind.
 */
class AnonymiseCustomerTest extends ApiTestCase
{
    public function test_the_owner_removes_name_phone_email_and_notes_for_good(): void
    {
        $salon = $this->installSalon();
        $customer = $this->customer($salon['business']->id);
        $this->signIn($salon['owner']);

        $this->postJson("api/v1/customers/{$customer->id}/anonymise", ['confirm' => 'REMOVE'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Removed customer')
            ->assertJsonPath('data.phone', null)
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.notes', null)
            ->assertJsonPath('data.is_removed', true);

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'Removed customer', 'phone_e164' => null, 'anonymised_by' => $salon['owner']->id]);
        $event = AuditEvent::query()->where('action', 'customer.anonymised')->sole();
        $this->assertSame($salon['owner']->id, $event->actor_id);
        $this->assertStringNotContainsString('Funke', (string) json_encode(AuditEvent::query()->get()->map->only(['before', 'after'])));
    }

    public function test_it_needs_the_typed_confirmation(): void
    {
        $salon = $this->installSalon();
        $customer = $this->customer($salon['business']->id);

        $this->signIn($salon['owner'])->postJson("api/v1/customers/{$customer->id}/anonymise", ['confirm' => 'remove'])
            ->assertStatus(422)
            ->assertJsonPath('errors.confirm.0', 'Type REMOVE to confirm.');
        $this->assertSame('Funke Adeyemi', $customer->refresh()->name);
    }

    public function test_a_supervisor_who_manages_customers_is_still_refused(): void
    {
        $salon = $this->installSalon();
        $customer = $this->customer($salon['business']->id);
        $supervisor = $this->staffMember($salon['business'], SystemRole::Supervisor);

        $this->signIn($supervisor)->postJson("api/v1/customers/{$customer->id}/anonymise", ['confirm' => 'REMOVE'])->assertForbidden();

        $this->assertSame('Funke Adeyemi', $customer->refresh()->name);
        $this->assertSame('owner', AuditEvent::query()->where('action', 'access.denied')->sole()->after['permission']);
    }

    public function test_a_removed_customer_cannot_be_edited_or_found_and_does_not_trigger_the_duplicate_warning(): void
    {
        $salon = $this->installSalon();
        $customer = $this->customer($salon['business']->id);
        $this->signIn($salon['owner'])->postJson("api/v1/customers/{$customer->id}/anonymise", ['confirm' => 'REMOVE'])->assertOk();

        $this->patchJson("api/v1/customers/{$customer->id}", ['version' => 2, 'name' => 'Funke again'])->assertStatus(409)->assertJsonPath('code', 'CUSTOMER_REMOVED');
        $this->postJson("api/v1/customers/{$customer->id}/anonymise", ['confirm' => 'REMOVE'])->assertStatus(409);
        $this->assertSame([], $this->getJson('api/v1/customers')->json('data'));
        $this->postJson('api/v1/customers', ['name' => 'New customer', 'phone' => '08030001234'])->assertCreated();
    }

    private function customer(int $businessId): Customer
    {
        return app(CurrentBusiness::class)->within($businessId, fn () => Customer::factory()->create([
            'name' => 'Funke Adeyemi', 'phone_e164' => '+2348030001234', 'email' => 'funke@example.test', 'notes' => 'Prefers medium',
        ]));
    }
}
