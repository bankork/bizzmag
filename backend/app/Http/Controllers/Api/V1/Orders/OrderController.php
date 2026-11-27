<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Domain\Execution\Actions\CancelOrder;
use App\Domain\Orders\Actions\AddItem;
use App\Domain\Orders\Actions\AssociateCustomer;
use App\Domain\Orders\Actions\CreateOrder;
use App\Domain\Orders\Actions\RequestAdditional;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Orders\Models\ServiceOrder;
use App\Http\Controllers\Api\V1\Concerns\FindsVisits;
use App\Http\Controllers\Controller;
use App\Http\Presenters\OrderPresenter;
use App\Http\Requests\V1\Orders\AddItemRequest;
use App\Http\Requests\V1\Orders\AssociateCustomerRequest;
use App\Http\Requests\V1\Orders\StoreOrderRequest;
use App\Http\Requests\V1\ReasonRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Visits (SRS §4.5): the board, one visit, opening a visit, adding items,
 * extra-service requests, linking a guest to a customer and cancelling.
 */
class OrderController extends Controller
{
    use FindsVisits;

    public const PER_PAGE = 50;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'filter.status' => ['nullable', Rule::in([...array_column(OrderStatus::cases(), 'value'), 'all'])],
            'filter.q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $viewer = $this->viewer($request);
        $status = OrderPresenter::canSeeAll($viewer) ? ($filters['filter']['status'] ?? OrderStatus::Open->value) : OrderStatus::Open->value;
        $search = trim((string) ($filters['filter']['q'] ?? ''));

        $orders = ServiceOrder::query()
            ->when($status !== 'all', fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn (Builder $match) => $match->where('reference', 'like', $like)
                    ->orWhere('guest_name', 'like', $like)
                    ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like)));
            })
            ->when($status === OrderStatus::Open->value, fn (Builder $query) => $query->orderBy('opened_at')->orderBy('id'),
                fn (Builder $query) => $query->orderByDesc('opened_at')->orderByDesc('id'))
            ->paginate(self::PER_PAGE, page: (int) ($filters['page'] ?? 1));

        $collection = $orders->getCollection();
        $executions = OrderPresenter::load($collection);
        $presenter = OrderPresenter::for($viewer);

        return ApiResponse::success(
            $collection->map(fn (ServiceOrder $order) => $presenter->order($order, $executions))->values()->all(),
            ['pagination' => ['page' => $orders->currentPage(), 'per_page' => $orders->perPage(), 'total' => $orders->total(), 'last_page' => $orders->lastPage()]],
        );
    }

    public function show(Request $request, int $order): JsonResponse
    {
        return $this->orderResponse($request, $this->findOrder($order, $this->viewer($request))->id);
    }

    public function store(StoreOrderRequest $request, CreateOrder $create): JsonResponse
    {
        /** @var array{customer_id?: ?int, guest_name?: ?string, guest_phone?: ?string} $who */
        $who = $request->safe()->only(['customer_id', 'guest_name', 'guest_phone']);
        /** @var list<array{catalogue_item_id: int, quantity?: int, employee_ids?: list<int>}> $items */
        $items = array_values((array) $request->validated('items'));

        return $this->orderResponse($request, $create($this->viewer($request), $who, $items)->id, 201);
    }

    public function addItem(AddItemRequest $request, int $order, AddItem $add): JsonResponse
    {
        /** @var array{catalogue_item_id: int, quantity?: int, employee_ids?: list<int>} $spec */
        $spec = $request->safe()->only(['catalogue_item_id', 'quantity', 'employee_ids']);
        $item = $add($this->viewer($request), $this->findOrder($order), $spec);

        return $this->orderResponse($request, $item->service_order_id, 201);
    }

    public function requestAdditional(AddItemRequest $request, int $order, RequestAdditional $requestAdditional): JsonResponse
    {
        $note = $request->validated('note');
        $item = $requestAdditional(
            $this->viewer($request),
            $this->findOrder($order, $this->viewer($request)),
            (int) $request->validated('catalogue_item_id'),
            (int) ($request->validated('quantity') ?? 1),
            is_string($note) && trim($note) !== '' ? trim($note) : null,
        );

        return $this->orderResponse($request, $item->service_order_id, 201);
    }

    public function associateCustomer(AssociateCustomerRequest $request, int $order, AssociateCustomer $associate): JsonResponse
    {
        $associate($this->viewer($request), $this->findOrder($order), (int) $request->validated('customer_id'));

        return $this->orderResponse($request, $order);
    }

    public function cancel(ReasonRequest $request, int $order, CancelOrder $cancel): JsonResponse
    {
        $cancel($this->viewer($request), $this->findOrder($order), $request->reason());

        return $this->orderResponse($request, $order);
    }
}
