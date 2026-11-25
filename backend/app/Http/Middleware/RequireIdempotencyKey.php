<?php

namespace App\Http\Middleware;

use App\Domain\Shared\Exceptions\IdempotencyKeyRequired;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires a well-formed Idempotency-Key header on protected actions
 * (SRS §9). The key is made available to the controller as a request
 * attribute; the Action passes it to IdempotencyGuard.
 */
class RequireIdempotencyKey
{
    public const ATTRIBUTE = 'idempotency_key';

    public function handle(Request $request, Closure $next): Response
    {
        $key = (string) $request->headers->get(config('bizzmag.idempotency.header'), '');

        if (preg_match('/^[A-Za-z0-9\-_:.]{8,100}$/', $key) !== 1) {
            throw new IdempotencyKeyRequired;
        }

        $request->attributes->set(self::ATTRIBUTE, $key);

        return $next($request);
    }
}
