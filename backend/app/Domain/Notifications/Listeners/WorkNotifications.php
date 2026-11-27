<?php

namespace App\Domain\Notifications\Listeners;

use App\Domain\Execution\Events\ContributorsChanged;
use App\Domain\Execution\Events\DisputeDismissed;
use App\Domain\Execution\Events\ExecutionCancelled;
use App\Domain\Execution\Events\ExecutionCorrected;
use App\Domain\Execution\Events\ExecutionDisputed;
use App\Domain\Execution\Models\Execution;
use App\Domain\Notifications\Support\NotificationWriter;
use App\Domain\Notifications\Support\Recipients;
use App\Domain\Orders\Events\AdditionalServiceDecided;
use App\Domain\Orders\Events\AdditionalServiceRequested;
use App\Domain\Orders\Events\StaffAssigned;
use App\Domain\Orders\Models\OrderItem;
use App\Models\User;
use Illuminate\Events\Dispatcher;

/**
 * Turns visit and work events into in-app notifications (FR-NOT-002,
 * FR-EXE-011, FR-ADD-009). Runs after the change has committed. Texts name
 * the service, the visit reference and people, never amounts or contact
 * details (FR-NOT-004).
 */
final class WorkNotifications
{
    public function __construct(
        private readonly Recipients $recipients,
        private readonly NotificationWriter $writer,
    ) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(StaffAssigned::class, [self::class, 'staffAssigned']);
        $events->listen(AdditionalServiceRequested::class, [self::class, 'additionalRequested']);
        $events->listen(AdditionalServiceDecided::class, [self::class, 'additionalDecided']);
        $events->listen(ContributorsChanged::class, [self::class, 'contributorsChanged']);
        $events->listen(ExecutionDisputed::class, [self::class, 'disputed']);
        $events->listen(DisputeDismissed::class, [self::class, 'disputeDismissed']);
        $events->listen(ExecutionCorrected::class, [self::class, 'corrected']);
        $events->listen(ExecutionCancelled::class, [self::class, 'cancelled']);
    }

    public function staffAssigned(StaffAssigned $event): void
    {
        $item = $this->item($event->orderItemId);

        $this->writer->send($this->recipients->employees($event->employeeIds), 'order.assigned',
            "You are down to do {$item->name_snapshot}", $this->visit($item), $this->data($item), $event->actorId);
    }

    public function additionalRequested(AdditionalServiceRequested $event): void
    {
        $item = $this->item($event->orderItemId);
        $requester = $this->name($event->actorId);

        $this->writer->send($this->recipients->withPermission('additional.decide'), 'additional.pending',
            "Extra service waiting for approval: {$item->name_snapshot}", $this->visit($item).", asked for by {$requester}", $this->data($item), $event->actorId);
    }

    public function additionalDecided(AdditionalServiceDecided $event): void
    {
        $item = $this->item($event->orderItemId);
        $decision = $event->approved ? 'approved' : 'not approved';

        $this->writer->send($this->recipients->users([$item->requested_by]), 'additional.decided',
            "Extra service {$decision}: {$item->name_snapshot}", $this->visit($item), $this->data($item) + ['approved' => $event->approved], $event->actorId);
    }

    public function contributorsChanged(ContributorsChanged $event): void
    {
        $execution = $this->execution($event->executionId);
        $item = $execution->item;
        $by = $this->name($event->actorId);

        $this->writer->send($this->recipients->employees($event->addedEmployeeIds), 'execution.added',
            "You were added to {$item->name_snapshot}", $this->visit($item).", by {$by}", $this->data($item, $execution), $event->actorId);

        $this->writer->send($this->recipients->employees($event->removedEmployeeIds), 'execution.removed',
            "You were taken off {$item->name_snapshot}", $this->visit($item).", by {$by}", $this->data($item, $execution), $event->actorId);
    }

    public function disputed(ExecutionDisputed $event): void
    {
        $execution = $this->execution($event->executionId);

        $this->toPeopleAndCorrectors($execution, $execution->contributorIds(), 'execution.disputed',
            "{$execution->item->name_snapshot} was disputed", $event->actorId);
    }

    public function disputeDismissed(DisputeDismissed $event): void
    {
        $execution = $this->execution($event->executionId);
        $item = $execution->item;
        $people = [...$this->recipients->employees($execution->contributorIds()), ...$this->recipients->users(array_filter([$execution->disputed_by]))];

        $this->writer->send($people, 'execution.dispute_dismissed',
            "The dispute on {$item->name_snapshot} was dismissed", $this->visit($item), $this->data($item, $execution), $event->actorId);
    }

    public function corrected(ExecutionCorrected $event): void
    {
        $original = $this->execution($event->supersededId);
        $replacement = $this->execution($event->replacementId);
        $people = array_values(array_unique([...$original->contributorIds(), ...$replacement->contributorIds()]));

        $this->toPeopleAndCorrectors($replacement, $people, 'execution.corrected',
            "{$replacement->item->name_snapshot} was corrected by a supervisor", $event->actorId);
    }

    public function cancelled(ExecutionCancelled $event): void
    {
        $execution = $this->execution($event->executionId);

        $this->toPeopleAndCorrectors($execution, $execution->contributorIds(), 'execution.cancelled',
            "The record for {$execution->item->name_snapshot} was cancelled", $event->actorId);
    }

    /**
     * @param  list<int>  $employeeIds
     */
    private function toPeopleAndCorrectors(Execution $execution, array $employeeIds, string $type, string $title, int $actorId): void
    {
        $people = [...$this->recipients->employees($employeeIds), ...$this->recipients->withPermission('execution.correct')];

        $this->writer->send($people, $type, $title, $this->visit($execution->item), $this->data($execution->item, $execution), $actorId);
    }

    private function item(int $id): OrderItem
    {
        return OrderItem::query()->with('order.customer')->findOrFail($id);
    }

    private function execution(int $id): Execution
    {
        return Execution::query()->with(['contributors', 'item.order.customer'])->findOrFail($id);
    }

    private function visit(OrderItem $item): string
    {
        return "Visit {$item->order->reference} for {$item->order->displayName()}";
    }

    /**
     * @return array<string, int|string>
     */
    private function data(OrderItem $item, ?Execution $execution = null): array
    {
        return array_filter([
            'order_id' => $item->service_order_id,
            'order_item_id' => $item->id,
            'execution_id' => $execution?->id,
            'reference' => $item->order->reference,
        ], fn ($value) => $value !== null);
    }

    private function name(int $userId): string
    {
        return User::query()->whereKey($userId)->value('name') ?? 'a colleague';
    }
}
