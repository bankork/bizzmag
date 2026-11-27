<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Domain\Orders\Models\ItemStatus;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\OrderStatus;
use App\Http\Controllers\Api\V1\Concerns\FindsVisits;
use App\Http\Controllers\Controller;
use App\Http\Presenters\OrderPresenter;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The approvals queue (FR-ADD-009): extra services waiting for a decision
 * on open visits, oldest first. Over-limit discounts join it in P4.
 */
class ApprovalController extends Controller
{
    use FindsVisits;

    public const ADDITIONAL_SERVICE = 'additional_service';

    public function index(Request $request): JsonResponse
    {
        $request->validate(['filter.type' => ['nullable', Rule::in([self::ADDITIONAL_SERVICE])]]);

        $prices = OrderPresenter::canSeeAll($this->viewer($request));
        $items = OrderItem::query()
            ->with(['order.customer'])
            ->where('status', ItemStatus::PendingApproval)
            ->whereHas('order', fn (Builder $order) => $order->where('status', OrderStatus::Open))
            ->orderBy('created_at')->orderBy('id')
            ->limit(200)
            ->get();
        $requesters = User::query()->whereKey($items->pluck('requested_by')->unique()->all())->pluck('name', 'id');

        return ApiResponse::success($items->map(fn (OrderItem $item) => [
            'type' => self::ADDITIONAL_SERVICE,
            'order_item_id' => $item->id,
            'name' => $item->name_snapshot,
            'quantity' => $item->quantity,
            'note' => $item->request_note,
            'requested_by' => ['id' => $item->requested_by, 'name' => $requesters[$item->requested_by] ?? null],
            'requested_at' => $item->created_at?->toIso8601String(),
            'order' => ['id' => $item->service_order_id, 'reference' => $item->order->reference, 'customer_name' => $item->order->displayName()],
        ] + ($prices ? ['unit_price_kobo' => $item->unit_price_kobo, 'line_value_kobo' => $item->lineValueKobo()] : []))->values()->all());
    }
}
