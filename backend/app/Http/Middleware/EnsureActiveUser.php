<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Exceptions\AccountDisabled;
use App\Http\Support\SessionEnder;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A disabled user is refused on their very next request and signed out
 * (FR-AUTH-005, §14.22).
 */
class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->is_active) {
            SessionEnder::end($request);

            throw new AccountDisabled;
        }

        return $next($request);
    }
}
