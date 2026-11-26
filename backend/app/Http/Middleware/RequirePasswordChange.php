<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Exceptions\PasswordChangeRequired;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * After a password reset, the user may only read their profile, change
 * their password or sign out until they choose a new password.
 */
class RequirePasswordChange
{
    /**
     * @var list<string>
     */
    public const ALLOWED_ROUTES = ['api.v1.me', 'api.v1.me.password', 'api.v1.auth.logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->must_change_password && ! $request->routeIs(...self::ALLOWED_ROUTES)) {
            throw new PasswordChangeRequired;
        }

        return $next($request);
    }
}
