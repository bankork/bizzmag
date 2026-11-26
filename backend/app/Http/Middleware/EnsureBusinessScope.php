<?php

namespace App\Http\Middleware;

use App\Domain\Identity\Exceptions\NoBusinessAccess;
use App\Domain\Identity\Support\Membership;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sets the business for this request from the user's membership
 * (FR-IAM-007, SEC-003). From here on, business-owned queries and
 * permission checks are limited to that business.
 */
class EnsureBusinessScope
{
    public function __construct(
        private readonly Membership $membership,
        private readonly CurrentBusiness $business,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        $businessId = $this->membership->activeBusinessId($user);

        if ($businessId === null) {
            throw new NoBusinessAccess;
        }

        $this->business->set($businessId);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return $next($request);
    }
}
