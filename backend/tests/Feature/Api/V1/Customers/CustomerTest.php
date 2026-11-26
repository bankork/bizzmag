<?php

namespace Tests\Feature\Api\V1\Customers;

use App\Domain\Audit\AuditEvent;
use App\Domain\Business\Models\Business;
use App\Domain\Customers\Models\Customer;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\V1\ApiTestCase;

/**
 * FR-CUS-001, 002, 008 and 009: registering, editing and finding customers,
 * with the duplicate-phone warning.
 */
class CustomerTest extends ApiTestCase
{
    public function test_the_desk_registers_a_customer_with_a_normalised_phone(): void
    {
        $salon = $this->installSalon();
        $this->signIn($this->staffMember($salon['business'], SystemRole::Cashier));

        $this->postJson('api/v1/customers', ['name' => ' Funke Adeyemi ', 'phone' => '0803 000 1234', 'email' => 'Funke@Example.TEST', 'notes' => 'Prefers medium knotless'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Funke Adeyemi')
            ->assertJsonPath('data.phone', '+2348030001234')
            ->assertJsonPath('data.phone_display', '0803 000 1234')
            ->assertJsonPath('data.email', 'funke@example.test')
            ->assertJsonPath('data.is_removed', false);
    }

    public function test_only_a_name_is_required(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/customers', [])->assertStatus(422)->assertJsonValidationErrors('name');
        $this->postJson('api/v1/customers', ['name' => 'Amaka Obi', 'phone' => ''])->assertCreated()->assertJsonPath('data.phone', null)->assertJsonPath('data.has_phone', false);
        $this->postJson('api/v1/customers', ['name' => 'Amaka Obi', 'phone' => '12345'])->assertStatus(422)->assertJsonValidationErrors('phone');
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function waysOfWritingTheSameNumber(): array
    {
        return [
            'local' => ['08030001234'],
            'local with spaces' => ['0803 000 1234'],
            'country code' => ['2348030001234'],
            'international' => ['+234 803 000 1234'],
        ];
    }

    #[DataProvider('waysOfWritingTheSameNumber')]
    public function test_a_number_another_customer_has_needs_confirmation(string $typed): void
    {
        $salon = $this->installSalon();
        $this->inSalon($salon, fn () => Customer::factory()->create(['name' => 'Funke Adeyemi', 'phone_e164' => '+2348030001234']));
        $this->signIn($salon['owner']);

        $this->postJson('api/v1/customers', ['name' => 'Bisi Adeyemi', 'phone' => $typed])
            ->assertStatus(409)
            ->assertJsonPath('code', 'DUPLICATE_PHONE')
            ->assertJsonPath('message', 'This number already belongs to Funke Adeyemi. Add anyway?')
            ->assertJsonPath('data.matches.0.name', 'Funke Adeyemi');
        $this->assertDatabaseCount('customers', 1);

        $this->postJson('api/v1/customers', ['name' => 'Bisi Adeyemi', 'phone' => $typed, 'confirm_duplicate' => true])->assertCreated();
        $this->assertDatabaseCount('customers', 2);
        $this->assertTrue(AuditEvent::query()->where('action', 'customer.created')->orderByDesc('id')->first()?->after['duplicate_confirmed']);
    }

    public function test_changing_to_a_number_another_customer_has_needs_confirmation(): void
    {
        $salon = $this->installSalon();
        [$funke, $bisi] = $this->inSalon($salon, fn (): array => [
            Customer::factory()->create(['name' => 'Funke Adeyemi', 'phone_e164' => '+2348030001234']),
            Customer::factory()->create(['name' => 'Bisi Adeyemi', 'phone_e164' => '+2348030005678']),
        ]);
        $this->signIn($salon['owner']);

        $this->patchJson("api/v1/customers/{$bisi->id}", ['version' => 1, 'phone' => '0803 000 1234'])->assertStatus(409)->assertJsonPath('code', 'DUPLICATE_PHONE');
        $this->patchJson("api/v1/customers/{$bisi->id}", ['version' => 1, 'phone' => '0803 000 1234', 'confirm_duplicate' => true])->assertOk()->assertJsonPath('data.version', 2);
        $this->patchJson("api/v1/customers/{$funke->id}", ['version' => 1, 'name' => 'Funke A. Adeyemi'])->assertOk();
    }

    public function test_an_edit_based_on_an_old_version_is_refused(): void
    {
        $salon = $this->installSalon();
        $customer = $this->inSalon($salon, fn () => Customer::factory()->create());
        $this->signIn($salon['owner']);

        $this->patchJson("api/v1/customers/{$customer->id}", ['version' => 1, 'notes' => 'Allergic to some glues'])->assertOk();
        $this->patchJson("api/v1/customers/{$customer->id}", ['version' => 1, 'notes' => 'Overwritten'])->assertStatus(409)->assertJsonPath('code', 'STALE');
    }

    public function test_the_audit_log_names_changed_fields_but_never_their_values(): void
    {
        $salon = $this->installSalon();
        $this->signIn($salon['owner']);
        $id = $this->postJson('api/v1/customers', ['name' => 'Funke Adeyemi', 'phone' => '08030001234', 'notes' => 'Secret note'])->json('data.id');
        $this->patchJson("api/v1/customers/{$id}", ['version' => 1, 'phone' => '08030009999', 'email' => 'funke@example.test'])->assertOk();

        $events = AuditEvent::query()->where('subject_type', 'customer')->orderBy('id')->get();

        $this->assertSame(['name', 'phone_e164', 'notes'], $events[0]->after['fields']);
        $this->assertSame(['phone_e164', 'email'], $events[1]->after['fields']);
        $logged = json_encode($events->map->only(['before', 'after', 'reason'])->all());
        foreach (['Funke', '8030001234', '8030009999', 'funke@example.test', 'Secret note'] as $personal) {
            $this->assertStringNotContainsString($personal, (string) $logged);
        }
    }

    public function test_search_by_name_or_by_any_form_of_the_phone(): void
    {
        $salon = $this->installSalon();
        $this->inSalon($salon, function (): void {
            Customer::factory()->create(['name' => 'Funke Adeyemi', 'phone_e164' => '+2348030001234']);
            Customer::factory()->create(['name' => 'Chioma Nwosu', 'phone_e164' => '+2348160004821']);
            Customer::factory()->create(['name' => 'Tolu 100% Real', 'phone_e164' => null]);
        });
        $this->signIn($this->staffMember($salon['business'], SystemRole::Cashier));

        $this->assertSame(['Funke Adeyemi'], $this->names('adey'));
        $this->assertSame(['Funke Adeyemi'], $this->names('0803 000 1234'));
        $this->assertSame(['Funke Adeyemi'], $this->names('+2348030001234'));
        $this->assertSame(['Chioma Nwosu'], $this->names('4821'));
        $this->assertSame(['Chioma Nwosu'], $this->names('0816'));
        $this->assertSame(['Tolu 100% Real'], $this->names('100%'));
        $this->assertSame(['Chioma Nwosu', 'Funke Adeyemi', 'Tolu 100% Real'], $this->names(''));
    }

    public function test_search_results_are_paged_twenty_at_a_time(): void
    {
        $salon = $this->installSalon();
        $this->inSalon($salon, fn () => Customer::factory()->count(25)->create());
        $this->signIn($salon['owner']);

        $this->getJson('api/v1/customers')->assertJsonCount(20, 'data')->assertJsonPath('meta.pagination.total', 25);
        $this->getJson('api/v1/customers?page=2')->assertJsonCount(5, 'data');
    }

    public function test_without_contact_permission_phone_and_email_are_absent_and_phone_search_is_off(): void
    {
        $salon = $this->installSalon();
        $customer = $this->inSalon($salon, fn () => Customer::factory()->create(['name' => 'Funke Adeyemi', 'phone_e164' => '+2348030001234', 'email' => 'funke@example.test']));
        $this->signIn($this->userWith($salon, ['customer.view']));

        $list = $this->getJson('api/v1/customers')->assertOk();
        $this->assertArrayNotHasKey('phone', $list->json('data.0'));
        $this->assertArrayNotHasKey('email', $list->json('data.0'));
        $this->assertTrue($list->json('data.0.has_phone'));
        $this->assertStringNotContainsString('8030001234', $list->getContent());
        $this->getJson("api/v1/customers/{$customer->id}")->assertJsonMissingPath('data.phone')->assertJsonMissingPath('data.email');
        $this->assertSame([], $this->names('08030001234'));
    }

    /**
     * @return list<string>
     */
    private function names(string $query): array
    {
        return array_column($this->getJson('api/v1/customers?filter[q]='.urlencode($query))->assertOk()->json('data'), 'name');
    }

    /**
     * @param  array{business: Business}  $salon
     * @param  list<string>  $permissions
     */
    private function userWith(array $salon, array $permissions): User
    {
        $user = $this->staffMember($salon['business'], SystemRole::Employee);

        return $this->inSalon($salon, function () use ($salon, $user, $permissions): User {
            $role = Role::query()->create(['name' => 'Custom '.implode(' ', $permissions), 'guard_name' => 'web', 'business_id' => $salon['business']->id]);
            $role->givePermissionTo($permissions);
            $user->syncRoles([$role]);

            return $user->refresh();
        });
    }

    /**
     * @template T
     *
     * @param  array{business: Business}  $salon
     * @param  callable(): T  $callback
     * @return T
     */
    private function inSalon(array $salon, callable $callback): mixed
    {
        return app(CurrentBusiness::class)->within($salon['business']->id, $callback);
    }
}
