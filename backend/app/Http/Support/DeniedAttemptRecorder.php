<?php

namespace App\Http\Support;

use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\AccessDenied;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Records refused attempts at protected actions (SEC-016): who, which
 * permission, which endpoint. Called by the exception handler, outside any
 * business transaction, so the record survives the refusal.
 */
final class DeniedAttemptRecorder
{
    public static function shouldRecord(Throwable $exception): bool
    {
        // Policy and Gate refusals reach the handler already converted to a
        // 403 HTTP exception.
        return $exception instanceof AccessDenied
            || ($exception instanceof HttpExceptionInterface && $exception->getStatusCode() === 403);
    }

    public static function record(Throwable $exception, Request $request): void
    {
        try {
            app(AuditRecorder::class)->record(new AuditEntry(
                'access.denied',
                after: [
                    'permission' => $exception instanceof AccessDenied ? $exception->permission : null,
                    'method' => $request->method(),
                    'path' => '/'.ltrim($request->path(), '/'),
                    'route' => $request->route()?->getName(),
                ],
            ));
        } catch (Throwable $failure) {
            report($failure);
        }
    }
}
