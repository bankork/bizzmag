<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Models\Role;
use App\Domain\Shared\Exceptions\AccessDenied;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * For the few actions the requirements reserve for the owner, on top of a
 * permission: removing a customer's personal data (SEC-021, Stage 3 §8.2).
 * The Owner role is recognised by its system key, which cannot be renamed
 * or given to a custom role. Used as `owner`, after `permission:...`.
 */
class RequireOwner
{
    public const MARKER = 'owner';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->roles->contains(fn (Role $role) => $role->isOwner())) {
            return $next($request);
        }

        throw AccessDenied::missing(self::MARKER);
    }
}
