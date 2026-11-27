<?php

namespace App\Domain\Notifications\Support;

use App\Domain\Notifications\Models\InAppNotification;

/**
 * Writes the same notification for several people, never to the person
 * whose action caused it.
 */
final class NotificationWriter
{
    /**
     * @param  list<int>  $userIds
     * @param  array<string, mixed>  $data  IDs and references only; no amounts or contact details.
     */
    public function send(array $userIds, string $type, string $title, ?string $body, array $data, ?int $exceptUserId = null): void
    {
        foreach (array_unique($userIds) as $userId) {
            if ($userId === $exceptUserId) {
                continue;
            }

            InAppNotification::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'title' => mb_substr($title, 0, 150),
                'body' => $body === null ? null : mb_substr($body, 0, 300),
                'data' => $data,
            ]);
        }
    }
}
