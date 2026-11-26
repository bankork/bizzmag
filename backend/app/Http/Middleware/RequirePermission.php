<?php

namespace App\Http\Middleware;

use App\Domain\Shared\Exceptions\AccessDenied;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level permission check (FR-IAM-004, SEC-002), used as
 * `permission:user.manage`. Several permissions separated by | mean any
 * one of them is enough.
 *
 * Authorisation is always by permission, never by role name (SRS §3.1).
 */
class RequirePermission
{
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();
        $required = explode('|', $permissions);

        if (! $user instanceof User) {
            throw AccessDenied::missing($permissions);
        }

        foreach ($required as $permission) {
            if ($user->checkPermissionTo($permission)) {
                return $next($request);
            }
        }

        throw AccessDenied::missing($permissions);
    }
}
