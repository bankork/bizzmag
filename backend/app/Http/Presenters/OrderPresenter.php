<?php

namespace App\Http\Presenters;

use App\Domain\Execution\Models\Execution;
use App\Domain\Execution\Models\ExecutionContributor;
use App\Domain\Identity\Support\SalonStaff;
use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\OrderItemAssignment;
use App\Domain\Orders\Models\ServiceOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * API view of visits, their items and the recorded work (FR-EXE-001).
 *
 * Two shapes. With order.view_all: prices, overrides and the subtotal,
 * plus phone numbers with customer.view_contact. Without it (employees):
 * the customer's name and the work, and the price, contact and bill fields
 * are left out of the JSON entirely, not set to null (BR-041, §14.23).
 */
final class OrderPresenter
{
    private function __construct(
        private readonly bool $prices,
        private readonly bool $contact,
        private readonly ?int $viewerEmployeeId,
    ) {}

    public static function for(User $viewer): self
    {
        return new self(
            $viewer->checkPermissionTo('order.view_all'),
            $viewer->checkPermissionTo('order.view_all') && CustomerPresenter::canSeeContact($viewer),
            app(SalonStaff::class)->profileFor($viewer)?->id,
        );
    }

    public static function canSeeAll(User $viewer): bool
    {
        return $viewer->checkPermissionTo('order.view_all');
    }

    /**
     * Load what the views need for these visits in a fixed number of queries.
     *
     * @param  Collection<int, ServiceOrder>  $orders
     * @return array<int, Collection<int, Execution>> Executions by order item ID.
     */
    public static function load(Collection $orders): array
    {
        $orders->load(['customer', 'creator', 'items.assignments.employee']);
        $itemIds = $orders->flatMap(fn (ServiceOrder $order) => $order->items->modelKeys())->all();

        return self::executionsFor($itemIds);
    }

    /**
     * @param  list<int>  $itemIds
     * @return array<int, Collection<int, Execution>>
     */
    public static function executionsFor(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }

        return Execution::query()
            ->with(['contributors.employee', 'submitter', 'disputer'])
            ->whereIn('order_item_id', $itemIds)
            ->orderBy('id')
            ->get()
            ->groupBy('order_item_id')
            ->all();
    }

    /**
     * @param  array<int, Collection<int, Execution>>  $executions
     * @return array<string, mixed>
     */
    public function order(ServiceOrder $order, array $executions): array
    {
        $items = $order->items->map(fn (OrderItem $item) => $this->item($item, $executions[$item->id] ?? new Collection))->values()->all();
        $services = array_filter($items, fn (array $item) => $item['type'] === 'service' && in_array($item['status'], ItemStatus::workable(), true));

        $view = [
            'id' => $order->id,
            'reference' => $order->reference,
            'status' => $order->status->value,
            'customer_name' => $order->displayName(),
            'is_guest' => $order->customer_id === null,
            'opened_at' => $order->opened_at->toIso8601String(),
            'opened_business_day' => $order->opened_business_day,
            'opened_by' => $order->creator->name,
            'cancelled_at' => $order->cancelled_at?->toIso8601String(),
            'cancel_reason' => $order->cancel_reason,
            'progress' => [
                'services' => count($services),
                'recorded' => count(array_filter($services, fn (array $item) => $item['work'] !== null && $item['work']['status'] === 'confirmed')),
                'disputed' => count(array_filter($services, fn (array $item) => $item['work'] !== null && $item['work']['status'] === 'disputed')),
                'waiting' => count(array_filter($services, fn (array $item) => $item['work'] === null)),
                'pending_approval' => count(array_filter($items, fn (array $item) => $item['status'] === ItemStatus::PendingApproval->value)),
            ],
            'items' => $items,
        ];

        if ($this->prices) {
            $view['customer_id'] = $order->customer_id;
            $view['subtotal_kobo'] = $order->items->filter(fn (OrderItem $item) => $item->status->isBillable())->sum(fn (OrderItem $item) => $item->lineValueKobo());
        }

        if ($this->contact) {
            $phone = $order->customer !== null ? $order->customer->phone_e164 : $order->guest_phone_e164;
            $view['phone'] = $phone;
            $view['phone_display'] = CustomerPresenter::localPhone($phone);
        }

        return $view;
    }

    /**
     * @param  Collection<int, Execution>  $executions
     * @return array<string, mixed>
     */
    public function item(OrderItem $item, Collection $executions): array
    {
        $active = $executions->first(fn (Execution $execution) => $execution->isActive());
        $assigned = $item->assignments->map(fn (OrderItemAssignment $assignment) => [
            'employee_id' => $assignment->employee_id,
            'name' => $assignment->employee->display_name,
        ])->values()->all();

        $view = [
            'id' => $item->id,
            'type' => $item->type->value,
            'name' => $item->name_snapshot,
            'quantity' => $item->quantity,
            'status' => $item->status->value,
            'origin' => $item->origin->value,
            'request_note' => $item->request_note,
            'decision_reason' => $item->decision_reason,
            'cancel_reason' => $item->cancel_reason,
            'requested_by' => $item->requested_by,
            'assigned' => $assigned,
            'assigned_to_me' => $this->viewerEmployeeId !== null && in_array($this->viewerEmployeeId, array_column($assigned, 'employee_id'), true),
            'work' => $active === null ? null : $this->execution($active),
            'history' => $executions->reject(fn (Execution $execution) => $execution->isActive())
                ->map(fn (Execution $execution) => $this->execution($execution))->values()->all(),
        ];

        if ($this->prices) {
            $view += [
                'unit_price_kobo' => $item->unit_price_kobo,
                'override_price_kobo' => $item->override_price_kobo,
                'override_reason' => $item->override_reason,
                'line_value_kobo' => $item->lineValueKobo(),
                'taxable' => $item->taxable_snapshot,
            ];
        }

        return $view;
    }

    /**
     * A recorded service. Shares are percentages of credit, not money.
     *
     * @return array<string, mixed>
     */
    public function execution(Execution $execution): array
    {
        $contributors = $execution->contributors->map(fn (ExecutionContributor $contributor) => [
            'employee_id' => $contributor->employee_id,
            'name' => $contributor->employee->display_name,
            'is_primary' => $contributor->is_primary,
            'share_bp' => $contributor->share_bp,
        ])->values()->all();

        return [
            'id' => $execution->id,
            'order_item_id' => $execution->order_item_id,
            'status' => $execution->status->value,
            'late' => $execution->late,
            'submitted_by' => ['id' => $execution->submitted_by, 'name' => $execution->submitter->name],
            'submitted_at' => $execution->submitted_at->toIso8601String(),
            'allocation_method' => $execution->allocation_method->value,
            'primary_share_bp' => $execution->primary_share_bp,
            'supersedes_id' => $execution->supersedes_id,
            'override_reason' => $execution->override_reason,
            'dispute_reason' => $execution->dispute_reason,
            'disputed_by' => $execution->disputer?->name,
            'resolution_reason' => $execution->resolution_reason,
            'includes_me' => $this->viewerEmployeeId !== null && in_array($this->viewerEmployeeId, array_column($contributors, 'employee_id'), true),
            'contributors' => $contributors,
        ];
    }
}
