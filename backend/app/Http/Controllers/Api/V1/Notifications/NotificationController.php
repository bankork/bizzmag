<?php

namespace App\Http\Controllers\Api\V1\Notifications;

use App\Domain\Notifications\Actions\MarkNotificationsRead;
use App\Domain\Notifications\Models\InAppNotification;
use App\Http\Controllers\Api\V1\Concerns\FindsVisits;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The signed-in person's notifications (FR-NOT-001): the latest 50 and the
 * unread count, polled by the web app every 30 seconds.
 */
class NotificationController extends Controller
{
    use FindsVisits;

    public const LIMIT = 50;

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate(['unread' => ['nullable', 'boolean']]);
        $user = $this->viewer($request);
        $mine = InAppNotification::query()->where('user_id', $user->id);

        $notifications = (clone $mine)
            ->when((bool) ($filters['unread'] ?? false), fn ($query) => $query->whereNull('read_at'))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        return ApiResponse::success(
            $notifications->map(fn (InAppNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'body' => $notification->body,
                'data' => $notification->data ?? (object) [],
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at->toIso8601String(),
            ])->values()->all(),
            ['unread_count' => (clone $mine)->whereNull('read_at')->count()],
        );
    }

    public function read(Request $request, MarkNotificationsRead $markRead): JsonResponse
    {
        $input = $request->validate([
            'ids' => ['required_without:all', 'array', 'max:100'],
            'ids.*' => ['integer'],
            'all' => ['sometimes', 'accepted'],
        ]);

        $ids = isset($input['all']) ? null : array_values(array_map('intval', $input['ids'] ?? []));
        $count = $markRead($this->viewer($request), $ids);

        return ApiResponse::success(['marked' => $count]);
    }
}
