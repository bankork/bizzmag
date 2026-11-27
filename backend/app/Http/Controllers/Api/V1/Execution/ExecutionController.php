<?php

namespace App\Http\Controllers\Api\V1\Execution;

use App\Domain\Execution\Actions\CancelExecution;
use App\Domain\Execution\Actions\CorrectExecution;
use App\Domain\Execution\Actions\DismissDispute;
use App\Domain\Execution\Actions\DisputeExecution;
use App\Domain\Execution\Actions\EditContributors;
use App\Domain\Execution\Actions\JoinExecution;
use App\Domain\Execution\Actions\OverrideAllocation;
use App\Domain\Execution\Actions\SubmitExecution;
use App\Domain\Execution\Models\Execution;
use App\Domain\Identity\Support\SalonStaff;
use App\Domain\Orders\Models\OrderItem;
use App\Domain\Shared\Idempotency\IdempotencyGuard;
use App\Domain\Shared\Idempotency\IdempotentResult;
use App\Http\Controllers\Api\V1\Concerns\FindsVisits;
use App\Http\Controllers\Controller;
use App\Http\Middleware\RequireIdempotencyKey;
use App\Http\Presenters\OrderPresenter;
use App\Http\Requests\V1\Execution\AllocationRequest;
use App\Http\Requests\V1\Execution\ContributorsRequest;
use App\Http\Requests\V1\Execution\CorrectExecutionRequest;
use App\Http\Requests\V1\Execution\SubmitExecutionRequest;
use App\Http\Requests\V1\ReasonRequest;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

/**
 * Recorded work (SRS §4.6, §4.7, §6.3). Submitting needs an
 * Idempotency-Key, so a double tap or a retry on a weak signal records once
 * (§7.7.4). The other commands answer with the whole visit.
 */
class ExecutionController extends Controller
{
    use FindsVisits;

    public function store(SubmitExecutionRequest $request, int $orderItem, SubmitExecution $submit, IdempotencyGuard $guard): JsonResponse
    {
        $actor = $this->viewer($request);
        $item = $this->findItem($orderItem);
        $payload = ['order_item_id' => $item->id, 'contributors' => $request->colleagueIds(), 'primary' => $request->primaryEmployeeId()];

        $result = $guard->run('user:'.$actor->id, (string) $request->attributes->get(RequireIdempotencyKey::ATTRIBUTE), $payload, function () use ($actor, $item, $request, $submit): IdempotentResult {
            $execution = $submit($actor, $item, $request->colleagueIds(), $request->primaryEmployeeId());
            $execution->load(['contributors.employee', 'submitter', 'disputer']);

            return IdempotentResult::fresh(201, OrderPresenter::for($actor)->execution($execution) + ['order_id' => $item->service_order_id]);
        });

        return ApiResponse::fromIdempotent($result);
    }

    public function join(Request $request, int $execution, JoinExecution $join): JsonResponse
    {
        return $this->respond($request, $join($this->viewer($request), $this->findExecution($execution)));
    }

    public function contributors(ContributorsRequest $request, int $execution, EditContributors $edit): JsonResponse
    {
        return $this->respond($request, $edit($this->viewer($request), $this->findExecution($execution), $request->employeeIds()));
    }

    public function allocation(AllocationRequest $request, int $execution, OverrideAllocation $override): JsonResponse
    {
        $method = $request->splitMethod() ?? throw new LogicException('The method is validated as required.');

        return $this->respond($request, $override(
            $this->viewer($request), $this->findExecution($execution), $method, $request->reason(),
            $request->primaryEmployeeId(), $request->primaryShareBp(), $request->customShares(),
        ));
    }

    public function dispute(ReasonRequest $request, int $execution, DisputeExecution $dispute): JsonResponse
    {
        return $this->respond($request, $dispute($this->viewer($request), $this->findExecution($execution), $request->reason()));
    }

    public function dismissDispute(ReasonRequest $request, int $execution, DismissDispute $dismiss): JsonResponse
    {
        return $this->respond($request, $dismiss($this->viewer($request), $this->findExecution($execution), $request->reason()));
    }

    public function correct(CorrectExecutionRequest $request, int $execution, CorrectExecution $correct): JsonResponse
    {
        return $this->respond($request, $correct(
            $this->viewer($request), $this->findExecution($execution), $request->employeeIds(), $request->primaryEmployeeId(),
            $request->reason(), $request->splitMethod(), $request->primaryShareBp(), $request->customShares(),
        ));
    }

    public function cancel(ReasonRequest $request, int $execution, CancelExecution $cancel): JsonResponse
    {
        return $this->respond($request, $cancel($this->viewer($request), $this->findExecution($execution), $request->reason()));
    }

    /**
     * The person's own recorded work, newest first (UI-010 "submission history").
     */
    public function mine(Request $request, SalonStaff $staff): JsonResponse
    {
        $page = (int) ($request->validate(['page' => ['nullable', 'integer', 'min:1']])['page'] ?? 1);
        $viewer = $this->viewer($request);
        $own = $staff->profileFor($viewer) ?? $viewer->employeeProfile;

        if ($own === null) {
            return ApiResponse::success([], ['pagination' => ['page' => 1, 'per_page' => 30, 'total' => 0, 'last_page' => 1]]);
        }

        $executions = Execution::query()
            ->with(['contributors.employee', 'submitter', 'disputer', 'item.order.customer'])
            ->whereHas('contributors', fn ($contributor) => $contributor->where('employee_id', $own->id))
            ->orderByDesc('submitted_at')->orderByDesc('id')
            ->paginate(30, page: $page);
        $presenter = OrderPresenter::for($viewer);

        return ApiResponse::success(
            $executions->getCollection()->map(fn (Execution $execution) => $presenter->execution($execution) + [
                'item_name' => $execution->item->name_snapshot,
                'order' => ['id' => $execution->item->service_order_id, 'reference' => $execution->item->order->reference, 'customer_name' => $execution->item->order->displayName(), 'status' => $execution->item->order->status->value],
                'my_share_bp' => $execution->contributors->firstWhere('employee_id', $own->id)?->share_bp,
            ])->values()->all(),
            ['pagination' => ['page' => $executions->currentPage(), 'per_page' => $executions->perPage(), 'total' => $executions->total(), 'last_page' => $executions->lastPage()]],
        );
    }

    private function respond(Request $request, Execution $execution): JsonResponse
    {
        $orderId = (int) OrderItem::query()->whereKey($execution->order_item_id)->value('service_order_id');

        return $this->orderResponse($request, $orderId);
    }
}
