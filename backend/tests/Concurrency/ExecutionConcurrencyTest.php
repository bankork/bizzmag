<?php

namespace Tests\Concurrency;

use App\Domain\Execution\Models\Execution;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Orders\Models\OrderItem;
use PHPUnit\Framework\Attributes\Group;
use Tests\Support\BuildsSalon;
use Tests\Support\BuildsVisits;

/**
 * Simultaneous actions on one service from separate PHP processes, each
 * with its own database connection (SRS §14.14, §7.7.2).
 */
#[Group('concurrency')]
class ExecutionConcurrencyTest extends ConcurrencyTestCase
{
    use BuildsSalon;
    use BuildsVisits;

    public function test_four_stylists_submitting_the_same_service_at_once_make_exactly_one_record(): void
    {
        $salon = $this->installSalon();
        $stylists = array_map(fn () => $this->staffMember($salon['business'], SystemRole::Employee), range(1, 4));
        $order = $this->openVisit($salon['business'], $salon['owner'], [['catalogue_item_id' => $this->service($salon['business'], 'Knotless braids', 4_000_000)->id]]);
        $item = $this->firstItem($order);

        $outputs = $this->runWorkersTogether('execution.php', array_map(
            fn ($stylist) => [(string) $salon['business']->id, (string) $item->id, (string) $stylist->id, 'submit'],
            $stylists,
        ));

        sort($outputs);
        $this->assertSame(['created', 'exists', 'exists', 'exists'], $outputs);
        $this->assertSame(1, Execution::query()->withoutGlobalScopes()->where('order_item_id', $item->id)->count());
    }

    public function test_a_cancellation_racing_a_submission_never_leaves_work_on_a_cancelled_service(): void
    {
        $salon = $this->installSalon();
        $supervisor = $this->staffMember($salon['business'], SystemRole::Supervisor, withProfile: false);
        $stylist = $this->staffMember($salon['business'], SystemRole::Employee);
        $order = $this->openVisit($salon['business'], $salon['owner'], [['catalogue_item_id' => $this->service($salon['business'], 'Cornrows', 800_000)->id]]);
        $item = $this->firstItem($order);

        $outputs = $this->runWorkersTogether('execution.php', [
            [(string) $salon['business']->id, (string) $item->id, (string) $stylist->id, 'submit'],
            [(string) $salon['business']->id, (string) $item->id, (string) $supervisor->id, 'cancel'],
        ]);

        $this->assertSame('cancelled', $outputs[1]);
        $this->assertContains($outputs[0], ['created', 'refused:INVALID_TRANSITION']);
        $this->assertSame('cancelled', OrderItem::query()->withoutGlobalScopes()->findOrFail($item->id)->status->value);
        $this->assertSame(0, Execution::query()->withoutGlobalScopes()->where('order_item_id', $item->id)->whereNotNull('active_item_key')->count());
    }
}
