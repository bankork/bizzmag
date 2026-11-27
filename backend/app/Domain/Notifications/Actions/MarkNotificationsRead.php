<?php

namespace App\Domain\Notifications\Actions;

use App\Domain\Notifications\Models\InAppNotification;
use App\Models\User;

/**
 * Marks some or all of a person's own notifications as read.
 */
final class MarkNotificationsRead
{
    /**
     * @param  list<int>|null  $ids  Null marks everything read.
     */
    public function __invoke(User $user, ?array $ids): int
    {
        return InAppNotification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->when($ids !== null, fn ($query) => $query->whereKey($ids))
            ->update(['read_at' => now()]);
    }
}
