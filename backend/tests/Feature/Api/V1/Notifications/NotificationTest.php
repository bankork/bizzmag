<?php

namespace Tests\Feature\Api\V1\Notifications;

use App\Domain\Customers\Models\Customer;
use App\Domain\Execution\Actions\DisputeExecution;
use App\Domain\Notifications\Models\InAppNotification;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Feature\Api\V1\ApiTestCase;
use Tests\Support\BuildsVisits;

/**
 * In-app notifications (SRS §4.22, FR-NOT-001, FR-NOT-004).
 */
class NotificationTest extends ApiTestCase
{
    use BuildsVisits;

    public function test_people_see_only_their_own_notifications_with_an_unread_count_and_can_mark_them_read(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]);
        $this->record($salon['ada'], $this->firstItem($order), [$salon['bisi'], $salon['chi']]);

        $response = $this->as($salon['bisi'])->getJson('api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.unread_count', 1)
            ->assertJsonPath('data.0.type', 'execution.added')
            ->assertJsonPath('data.0.title', 'You were added to Cornrows')
            ->assertJsonPath('data.0.body', "Visit {$order->reference} for Walk-in, by Ada")
            ->assertJsonPath('data.0.data.order_id', $order->id)
            ->assertJsonPath('data.0.read', false);

        $this->as($salon['bisi'])->postJson('api/v1/notifications/read', ['ids' => [$response->json('data.0.id')]])->assertOk()->assertJsonPath('data.marked', 1);
        $this->as($salon['bisi'])->getJson('api/v1/notifications')->assertJsonPath('meta.unread_count', 0)->assertJsonPath('data.0.read', true);

        $this->as($salon['chi'])->postJson('api/v1/notifications/read', ['ids' => [$response->json('data.0.id')]])->assertJsonPath('data.marked', 0);
        $this->as($salon['chi'])->postJson('api/v1/notifications/read', ['all' => true])->assertJsonPath('data.marked', 1);
        $this->as($salon['ada'])->getJson('api/v1/notifications')->assertJsonCount(0, 'data');
    }

    public function test_notifications_never_contain_amounts_or_contact_details(): void
    {
        $salon = $this->salonWithStaff();
        $customer = app(CurrentBusiness::class)->within($salon['business']->id, fn () => Customer::factory()->create(['name' => 'Funke Adeyemi', 'phone_e164' => '+2348030004821']));
        $order = $this->openVisit($salon['business'], $salon['cashier'], [
            ['catalogue_item_id' => $this->service($salon['business'], 'Knotless braids', 4_000_000)->id, 'employee_ids' => [$this->profileOf($salon['chi'])->id]],
        ], ['customer_id' => $customer->id]);
        $execution = $this->record($salon['ada'], $this->firstItem($order), [$salon['bisi']]);
        $this->as($salon['bisi'])->postJson("api/v1/executions/{$execution->id}/dispute", ['reason' => 'Not me'])->assertOk();
        $this->as($salon['supervisor'])->postJson("api/v1/executions/{$execution->id}/cancel", ['reason' => 'Wrong visit'])->assertOk();
        $this->as($salon['ada'])->postJson("api/v1/orders/{$order->id}/additional-requests", ['catalogue_item_id' => $this->service($salon['business'], 'Hair wash', 300_000)->id])->assertCreated();

        $notifications = InAppNotification::query()->withoutGlobalScopes()->get();
        $this->assertGreaterThanOrEqual(8, $notifications->count());
        $this->assertEqualsCanonicalizing(
            ['order.assigned', 'execution.added', 'execution.disputed', 'execution.cancelled', 'additional.pending'],
            $notifications->pluck('type')->unique()->values()->all(),
        );

        foreach ($notifications as $notification) {
            $text = json_encode([$notification->title, $notification->body, $notification->data], JSON_THROW_ON_ERROR);
            $this->assertDoesNotMatchRegularExpression('/₦|\bNGN\b|40,000|4000000|kobo|0803|\+234/u', $text, $text);
        }
    }

    public function test_nothing_is_sent_when_the_change_is_rolled_back(): void
    {
        $salon = $this->salonWithStaff();
        $order = $this->openVisit($salon['business'], $salon['cashier'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]);
        $execution = $this->record($salon['ada'], $this->firstItem($order));
        $before = InAppNotification::query()->withoutGlobalScopes()->count();

        try {
            app(CurrentBusiness::class)->within($salon['business']->id, fn () => DB::transaction(function () use ($salon, $execution): void {
                app(DisputeExecution::class)($salon['supervisor'], $execution, 'Testing a rollback');

                throw new RuntimeException('Something failed after the dispute');
            }));
        } catch (RuntimeException) {
        }

        $this->assertSame('confirmed', $execution->refresh()->status->value);
        $this->assertSame($before, InAppNotification::query()->withoutGlobalScopes()->count());
    }

    public function test_notifications_older_than_ninety_days_are_removed(): void
    {
        $salon = $this->salonWithStaff();
        $make = fn (int $daysAgo) => InAppNotification::query()->withoutGlobalScopes()->forceCreate([
            'business_id' => $salon['business']->id, 'user_id' => $salon['ada']->id, 'type' => 'test', 'title' => 'Test', 'created_at' => now()->subDays($daysAgo),
        ]);
        $old = $make(91);
        $recent = $make(89);

        $this->artisan('model:prune', ['--model' => [InAppNotification::class]])->assertSuccessful();
        $this->artisan('model:prune', ['--model' => [InAppNotification::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
    }
}
