<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Domain\Customers\Models\Customer;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Orders\Models\ServiceOrder;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A customer's visits, newest first (FR-CUS-010, visits part). Invoices and
 * payments join this list in P4 and P5.
 */
class CustomerOrdersController extends Controller
{
    public function __invoke(Request $request, int $customer): JsonResponse
    {
        $page = (int) ($request->validate(['page' => ['nullable', 'integer', 'min:1']])['page'] ?? 1);
        $record = Customer::query()->whereKey($customer)->first() ?? throw new RecordNotFound;

        $orders = ServiceOrder::query()->with('items')->where('customer_id', $record->id)
            ->orderByDesc('opened_at')->orderByDesc('id')
            ->paginate(20, page: $page);

        return ApiResponse::success(
            $orders->getCollection()->map(fn (ServiceOrder $order) => [
                'id' => $order->id,
                'reference' => $order->reference,
                'status' => $order->status->value,
                'opened_at' => $order->opened_at->toIso8601String(),
                'items' => $order->items->filter(fn (OrderItem $item) => $item->status->isBillable())->map(fn (OrderItem $item) => $item->name_snapshot)->values()->all(),
                'subtotal_kobo' => $order->items->filter(fn (OrderItem $item) => $item->status->isBillable())->sum(fn (OrderItem $item) => $item->lineValueKobo()),
            ])->values()->all(),
            ['pagination' => ['page' => $orders->currentPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total(), 'last_page' => $orders->lastPage()]],
        );
    }
}
