<?php

use App\Domain\Shared\Exceptions\DomainError;
use App\Http\Middleware\AssignRequestId;
use App\Http\Middleware\EnforceIdleTimeout;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureBusinessScope;
use App\Http\Middleware\RequireIdempotencyKey;
use App\Http\Middleware\RequirePasswordChange;
use App\Http\Middleware\RequirePermission;
use App\Http\Responses\ApiExceptionRenderer;
use App\Http\Support\DeniedAttemptRecorder;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(AssignRequestId::class);

        // Sanctum SPA cookie sessions for the web app (SEC-023).
        $middleware->statefulApi();

        $middleware->alias([
            'idempotent' => RequireIdempotencyKey::class,
            'permission' => RequirePermission::class,
        ]);

        // Every protected route: signed in, active, scoped to the user's
        // business, within the idle limit, and not waiting for a new password.
        $middleware->group('signed-in', [
            'auth:sanctum',
            EnsureActiveUser::class,
            EnsureBusinessScope::class,
            EnforceIdleTimeout::class,
            RequirePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->dontReportWhen(
            fn (Throwable $exception) => $exception instanceof DomainError && $exception->httpStatus() < 500,
        );

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if (DeniedAttemptRecorder::shouldRecord($exception)) {
                DeniedAttemptRecorder::record($exception, $request);
            }

            return ApiExceptionRenderer::render($exception);
        });
    })->create();
