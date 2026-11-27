<?php

namespace Tests\Support;

use App\Domain\Business\Models\Business;
use App\Domain\Execution\Actions\SubmitExecution;
use App\Domain\Execution\Models\Execution;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Orders\Actions\CreateOrder;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;

/**
 * Visits and recorded work for tests, made through the real actions, so
 * every fixture went through the same rules, locks and audit as the app.
 */
trait BuildsVisits
{
    use BuildsCatalogue;

    /**
     * A salon with an owner, a supervisor, a cashier and three stylists.
     *
     * @return array{business: Business, owner: User, supervisor: User, cashier: User, ada: User, bisi: User, chi: User}
     */
    protected function salonWithStaff(): array
    {
        $salon = $this->installSalon();
        $business = $salon['business'];

        return [
            'business' => $business,
            'owner' => $salon['owner'],
            'supervisor' => $this->staffMember($business, SystemRole::Supervisor, ['name' => 'Tola Supervisor']),
            'cashier' => $this->staffMember($business, SystemRole::Cashier, ['name' => 'Kemi Cashier'], withProfile: false),
            'ada' => $this->staffMember($business, SystemRole::Employee, ['name' => 'Ada']),
            'bisi' => $this->staffMember($business, SystemRole::Employee, ['name' => 'Bisi']),
            'chi' => $this->staffMember($business, SystemRole::Employee, ['name' => 'Chi']),
        ];
    }

    /**
     * Opens a visit through CreateOrder.
     *
     * @param  list<array{catalogue_item_id: int, quantity?: int, employee_ids?: list<int>}>  $items
     * @param  array{customer_id?: ?int, guest_name?: ?string, guest_phone?: ?string}  $who
     */
    protected function openVisit(Business $business, User $actor, array $items, array $who = ['guest_name' => 'Walk-in']): ServiceOrder
    {
        return app(CurrentBusiness::class)->within($business->id, fn () => app(CreateOrder::class)($actor, $who, $items)->load('items'));
    }

    /**
     * Records work through SubmitExecution.
     *
     * @param  list<User>  $colleagues
     */
    protected function record(User $actor, OrderItem $item, array $colleagues = [], ?User $primary = null): Execution
    {
        return app(CurrentBusiness::class)->within($item->business_id, fn () => app(SubmitExecution::class)(
            $actor,
            $item,
            array_map(fn (User $colleague) => $this->profileOf($colleague)->id, $colleagues),
            $primary === null ? null : $this->profileOf($primary)->id,
        )->load('contributors'));
    }

    protected function profileOf(User $user): EmployeeProfile
    {
        return EmployeeProfile::query()->withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
    }

    protected function firstItem(ServiceOrder $order): OrderItem
    {
        return OrderItem::query()->withoutGlobalScopes()->where('service_order_id', $order->id)->orderBy('id')->firstOrFail();
    }

    /**
     * Shares on a record by person: ['Ada' => 3334, ...].
     *
     * @return array<string, int>
     */
    protected function sharesByName(Execution $execution): array
    {
        $execution->load(['contributors.employee' => fn ($query) => $query->withoutGlobalScopes()]);
        $shares = [];

        foreach ($execution->contributors as $contributor) {
            $shares[$contributor->employee->display_name] = $contributor->share_bp;
        }

        return $shares;
    }

    /**
     * Switch to another person between requests: a fresh session, as on
     * their own phone or laptop.
     */
    protected function as(User $user): static
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        return $this->signIn($user);
    }

    protected function idempotencyKey(): string
    {
        return 'test-'.bin2hex(random_bytes(8));
    }
}
