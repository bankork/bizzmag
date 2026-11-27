<?php

namespace App\Http\Controllers\Api\V1\Concerns;

use App\Domain\Execution\Models\Execution;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Http\Presenters\OrderPresenter;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Visits, items and records looked up inside the current business only
 * (SEC-003). Staff without order.view_all see open visits only, so a past
 * or cancelled visit answers "not found" for them.
 */
trait FindsVisits
{
    protected function findOrder(int $id, ?User $viewer = null): ServiceOrder
    {
        $order = ServiceOrder::query()->whereKey($id)->first() ?? throw new RecordNotFound;

        if ($viewer !== null && ! OrderPresenter::canSeeAll($viewer) && $order->status !== OrderStatus::Open) {
            throw new RecordNotFound;
        }

        return $order;
    }

    protected function findItem(int $id): OrderItem
    {
        return OrderItem::query()->whereKey($id)->first() ?? throw new RecordNotFound;
    }

    protected function findExecution(int $id): Execution
    {
        return Execution::query()->with('item')->whereKey($id)->first() ?? throw new RecordNotFound;
    }

    /**
     * The visit as the signed-in person may see it.
     */
    protected function orderResponse(Request $request, int $orderId, int $status = 200): JsonResponse
    {
        $viewer = $this->viewer($request);
        $orders = new Collection([ServiceOrder::query()->findOrFail($orderId)]);
        $executions = OrderPresenter::load($orders);

        return ApiResponse::success(OrderPresenter::for($viewer)->order($orders->firstOrFail(), $executions), status: $status);
    }

    protected function viewer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
