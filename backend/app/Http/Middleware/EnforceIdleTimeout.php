<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Exceptions\SessionIdle;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Http\Support\IdleLimit;
use App\Http\Support\SessionEnder;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ends a session that has been idle too long (SEC-018, SEC-023): 30 minutes
 * by default for anyone with more than the Employee permissions, 7 days for
 * employee-only users. The owner can change both in Settings.
 */
class EnforceIdleTimeout
{
    public const SESSION_KEY = 'last_activity_at';

    public function __construct(
        private readonly IdleLimit $limit,
        private readonly AuditRecorder $audit,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $request->hasSession()) {
            return $next($request);
        }

        $session = $request->session();
        $now = now()->getTimestamp();
        $lastActivity = $session->get(self::SESSION_KEY);

        if (is_int($lastActivity) && $now - $lastActivity > $this->limit->for($user)) {
            $this->audit->record(new AuditEntry('auth.session_expired', 'user', $user->id, actorId: $user->id));
            SessionEnder::end($request);

            throw new SessionIdle;
        }

        $session->put(self::SESSION_KEY, $now);

        return $next($request);
    }
}
