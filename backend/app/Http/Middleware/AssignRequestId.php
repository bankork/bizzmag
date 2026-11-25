<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gives every request an ID, shared with logs, audit events and the response.
 *
 * A client-supplied X-Request-Id is reused only when it looks safe, so log
 * lines cannot be polluted with arbitrary text.
 */
class AssignRequestId
{
    public const HEADER = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $incoming = (string) $request->headers->get(self::HEADER, '');

        $requestId = preg_match('/^[A-Za-z0-9\-_]{8,64}$/', $incoming) === 1
            ? $incoming
            : (string) Str::ulid();

        Context::add('request_id', $requestId);

        $response = $next($request);
        $response->headers->set(self::HEADER, $requestId);

        return $response;
    }
}
