<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Domain\Execution\Actions\CancelItem;
use App\Domain\Execution\Actions\WithdrawAdditional;
use App\Domain\Orders\Actions\AssignStaff;
use App\Domain\Orders\Actions\DecideAdditional;
use App\Domain\Orders\Actions\OverridePrice;
use App\Http\Controllers\Api\V1\Concerns\FindsVisits;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Orders\AssignmentsRequest;
use App\Http\Requests\V1\Orders\OptionalReasonRequest;
use App\Http\Requests\V1\Orders\PriceOverrideRequest;
use App\Http\Requests\V1\ReasonRequest;
use Illuminate\Http\JsonResponse;

/**
 * Changes to one item on a visit (SRS §6.2). Each answers with the whole
 * visit as the signed-in person may see it.
 */
class OrderItemController extends Controller
{
    use FindsVisits;

    public function assignments(AssignmentsRequest $request, int $orderItem, AssignStaff $assign): JsonResponse
    {
        $item = $assign($this->viewer($request), $this->findItem($orderItem), $request->employeeIds());

        return $this->orderResponse($request, $item->service_order_id);
    }

    public function cancel(ReasonRequest $request, int $orderItem, CancelItem $cancel): JsonResponse
    {
        $item = $cancel($this->viewer($request), $this->findItem($orderItem), $request->reason());

        return $this->orderResponse($request, $item->service_order_id);
    }

    public function priceOverride(PriceOverrideRequest $request, int $orderItem, OverridePrice $override): JsonResponse
    {
        $item = $override($this->viewer($request), $this->findItem($orderItem), (int) $request->validated('price_kobo'), trim((string) $request->validated('reason')));

        return $this->orderResponse($request, $item->service_order_id);
    }

    public function approve(OptionalReasonRequest $request, int $orderItem, DecideAdditional $decide): JsonResponse
    {
        $item = $decide->approve($this->viewer($request), $this->findItem($orderItem), $request->reason());

        return $this->orderResponse($request, $item->service_order_id);
    }

    public function reject(ReasonRequest $request, int $orderItem, DecideAdditional $decide): JsonResponse
    {
        $item = $decide->reject($this->viewer($request), $this->findItem($orderItem), $request->reason());

        return $this->orderResponse($request, $item->service_order_id);
    }

    public function withdraw(OptionalReasonRequest $request, int $orderItem, WithdrawAdditional $withdraw): JsonResponse
    {
        $item = $withdraw($this->viewer($request), $this->findItem($orderItem), $request->reason());

        return $this->orderResponse($request, $item->service_order_id);
    }
}
