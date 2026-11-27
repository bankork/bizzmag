<?php

namespace App\Domain\Execution\Support;

use App\Domain\Execution\Exceptions\LateWindowClosed;
use App\Domain\Orders\Exceptions\OrderNotOpen;
use App\Domain\Orders\Models\OrderStatus;
use App\Domain\Orders\Models\ServiceOrder;
use App\Models\User;

/**
 * When work can still be recorded on a visit (FR-EXE-013): while it is
 * open, and after finalisation until the late window ends. After that only
 * execution.submit_late may record. Finalisation arrives in P4; the rule is
 * in place already.
 */
final class SubmissionWindow
{
    /**
     * @return bool Whether the work is recorded after finalisation (late).
     */
    public function check(ServiceOrder $order, User $actor): bool
    {
        if ($order->status === OrderStatus::Open) {
            return false;
        }

        if ($order->status === OrderStatus::Cancelled) {
            throw new OrderNotOpen;
        }

        $withinWindow = $order->late_window_ends_at !== null && now()->lessThanOrEqualTo($order->late_window_ends_at);

        if (! $withinWindow && ! $actor->checkPermissionTo('execution.submit_late')) {
            throw new LateWindowClosed;
        }

        return true;
    }
}
