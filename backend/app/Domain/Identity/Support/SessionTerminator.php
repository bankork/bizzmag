<?php

namespace App\Domain\Identity\Support;

use Illuminate\Support\Facades\DB;

/**
 * Ends a user's sessions in the database session store, for example when
 * they are disabled or their password changes.
 */
final class SessionTerminator
{
    public function endAll(int $userId, ?string $exceptSessionId = null): int
    {
        if (config('session.driver') !== 'database') {
            return 0;
        }

        return DB::table((string) config('session.table', 'sessions'))
            ->where('user_id', $userId)
            ->when($exceptSessionId !== null, fn ($query) => $query->where('id', '!=', $exceptSessionId))
            ->delete();
    }
}
