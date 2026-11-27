<?php

namespace Tests\Feature\Api\V1\Orders;

use App\Domain\Business\Models\Business;
use App\Domain\Customers\Models\Customer;
use App\Domain\Identity\Models\Role;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Api\V1\ApiTestCase;
use Tests\Support\BuildsVisits;

/**
 * Employees never receive prices, customer contact details or bill data
 * (BR-041, FR-EXE-001, SRS §14.23). The fields are absent, not null.
 */
class EmployeeOrderViewTest extends ApiTestCase
{
    use BuildsVisits;

    private const FORBIDDEN_KEY = '/kobo|price|phone|email|subtotal|invoice|customer_id|taxable/i';

    public function test_an_employee_sees_the_visit_and_the_work_but_no_prices_or_contact_details(): void
    {
        $salon = $this->salonWithStaff();
        [$order, $item] = $this->visitWithWork($salon);

        foreach ([
            $this->as($salon['ada'])->getJson("api/v1/orders/{$order}"),
            $this->as($salon['ada'])->getJson('api/v1/orders'),
            $this->as($salon['ada'])->getJson('api/v1/executions/mine'),
            $this->as($salon['bisi'])->withHeader('Idempotency-Key', $this->idempotencyKey())->postJson("api/v1/order-items/{$item}/executions"),
        ] as $response) {
            $this->assertNoRestrictedKeys($response);
        }

        $this->as($salon['ada'])->getJson("api/v1/orders/{$order}")
            ->assertJsonPath('data.customer_name', 'Funke Adeyemi')
            ->assertJsonPath('data.items.0.name', 'Knotless braids')
            ->assertJsonPath('data.items.0.work.contributors.0.name', 'Ada')
            ->assertJsonPath('data.items.0.work.includes_me', true);
    }

    public function test_the_cashier_sees_prices_and_the_phone_number(): void
    {
        $salon = $this->salonWithStaff();
        [$order] = $this->visitWithWork($salon);

        $this->as($salon['cashier'])->getJson("api/v1/orders/{$order}")
            ->assertJsonPath('data.subtotal_kobo', 4_300_000)
            ->assertJsonPath('data.items.0.unit_price_kobo', 4_000_000)
            ->assertJsonPath('data.phone', '+2348030004821')
            ->assertJsonPath('data.phone_display', '0803 000 4821');
    }

    public function test_prices_without_contact_permission_leave_the_phone_out(): void
    {
        $salon = $this->salonWithStaff();
        [$order] = $this->visitWithWork($salon);
        $role = app(CurrentBusiness::class)->within($salon['business']->id, function () {
            $role = Role::query()->create(['name' => 'Visits only', 'guard_name' => 'web']);
            $role->givePermissionTo(['order.view_open', 'order.view_all']);

            return $role;
        });
        $viewer = $this->staffMember($salon['business'], [], withProfile: false);
        app(CurrentBusiness::class)->within($salon['business']->id, fn () => $viewer->syncRoles([$role]));

        $this->as($viewer->refresh())->getJson("api/v1/orders/{$order}")
            ->assertOk()->assertJsonPath('data.subtotal_kobo', 4_300_000)->assertJsonMissingPath('data.phone');
    }

    public function test_an_employee_cannot_see_a_visit_that_is_no_longer_open(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]);
        $this->as($salon['supervisor'])->postJson("api/v1/orders/{$order->id}/cancel", ['reason' => 'Left'])->assertOk();

        $this->as($salon['ada'])->getJson("api/v1/orders/{$order->id}")->assertNotFound();
        $this->as($salon['ada'])->getJson('api/v1/orders?filter[status]=cancelled')->assertOk()->assertJsonCount(0, 'data');
        $this->as($salon['cashier'])->getJson("api/v1/orders/{$order->id}")->assertOk();
    }

    public function test_assigned_services_are_marked_for_the_employee(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [
            ['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id, 'employee_ids' => [$this->profileOf($salon['bisi'])->id]],
        ]);

        $this->as($salon['bisi'])->getJson("api/v1/orders/{$order->id}")->assertJsonPath('data.items.0.assigned_to_me', true);
        $this->as($salon['ada'])->getJson("api/v1/orders/{$order->id}")->assertJsonPath('data.items.0.assigned_to_me', false);
    }

    public function test_the_staff_directory_has_names_but_no_contact_details(): void
    {
        $salon = $this->salonWithStaff();
        $salon['chi']->forceFill(['is_active' => false])->save();

        $response = $this->as($salon['ada'])->getJson('api/v1/staff-directory')->assertOk();

        $this->assertSame(['Ada', 'Bisi', 'Tola Supervisor'], array_column($response->json('data'), 'name'));
        $this->assertNoRestrictedKeys($response);
        $response->assertJsonPath('data.0.is_me', true);
    }

    /**
     * @param  array{business: Business, cashier: User, ada: User, bisi: User}  $salon
     * @return array{0: int, 1: int} The visit ID and a second, unrecorded item's ID.
     */
    private function visitWithWork(array $salon): array
    {
        $customer = app(CurrentBusiness::class)->within($salon['business']->id, fn () => Customer::factory()->create(['name' => 'Funke Adeyemi', 'phone_e164' => '+2348030004821', 'email' => 'funke@example.test']));
        $order = $this->openVisit($salon['business'], $salon['cashier'], [
            ['catalogue_item_id' => $this->service($salon['business'], 'Knotless braids', 4_000_000)->id],
            ['catalogue_item_id' => $this->service($salon['business'], 'Hair wash', 300_000)->id],
        ], ['customer_id' => $customer->id]);
        $this->record($salon['ada'], $this->firstItem($order));

        return [$order->id, $order->items[1]->id];
    }

    private function assertNoRestrictedKeys(TestResponse $response): void
    {
        $this->assertLessThan(300, $response->status(), (string) $response->getContent());

        $walk = function (mixed $value, string $path) use (&$walk): void {
            if (! is_array($value)) {
                return;
            }

            foreach ($value as $key => $child) {
                if (is_string($key)) {
                    $this->assertDoesNotMatchRegularExpression(self::FORBIDDEN_KEY, $key, "Employees must not receive {$path}.{$key}.");
                }

                $walk($child, "{$path}.{$key}");
            }
        };

        $walk($response->json(), 'response');
        $this->assertStringNotContainsString('0803', (string) $response->getContent());
        $this->assertStringNotContainsString('funke@example.test', (string) $response->getContent());
    }
}
