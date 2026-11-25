<?php

namespace App\Http\Responses;

use App\Domain\Shared\Exceptions\DomainError;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Context;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Renders every API failure in the standard error envelope (SRS §9, Stage 3 §4.5).
 *
 * Messages are written for the person using the app. Internal details are
 * never returned, except in local debug mode.
 */
final class ApiExceptionRenderer
{
    /**
     * @var array<int, array{0: string, 1: string}>
     */
    private const HTTP_CODES = [
        400 => ['BAD_REQUEST', 'The request could not be understood.'],
        403 => ['FORBIDDEN', 'You do not have permission to do this.'],
        404 => ['NOT_FOUND', 'The record was not found.'],
        405 => ['METHOD_NOT_ALLOWED', 'This action is not supported here.'],
        409 => ['CONFLICT', 'This record changed. Refresh and try again.'],
        413 => ['PAYLOAD_TOO_LARGE', 'The upload is too large.'],
        419 => ['CSRF_TOKEN_MISMATCH', 'Your session expired. Refresh the page and try again.'],
        429 => ['TOO_MANY_REQUESTS', 'Too many attempts. Please wait a moment and try again.'],
        503 => ['SERVICE_UNAVAILABLE', 'The service is temporarily unavailable. Please try again shortly.'],
    ];

    public static function render(Throwable $exception): ?JsonResponse
    {
        if ($exception instanceof HttpResponseException) {
            return null;
        }

        if ($exception instanceof DomainError) {
            return ApiResponse::error(
                $exception->errorCode(),
                $exception->getMessage(),
                $exception->httpStatus(),
                data: $exception->context(),
            );
        }

        if ($exception instanceof ValidationException) {
            return ApiResponse::error(
                'VALIDATION_FAILED',
                'Please check the highlighted fields.',
                $exception->status,
                errors: $exception->errors(),
            );
        }

        if ($exception instanceof AuthenticationException) {
            return ApiResponse::error('UNAUTHENTICATED', 'Please sign in to continue.', 401);
        }

        if ($exception instanceof HttpExceptionInterface) {
            $status = $exception->getStatusCode();
            [$code, $message] = self::HTTP_CODES[$status] ?? ['HTTP_ERROR', 'The request could not be completed.'];

            return ApiResponse::error($code, $message, $status)->withHeaders($exception->getHeaders());
        }

        return self::serverError($exception);
    }

    private static function serverError(Throwable $exception): JsonResponse
    {
        $reference = Context::get('request_id');
        $data = $reference !== null ? ['reference' => $reference] : [];

        if (config('app.debug') && ! app()->isProduction()) {
            $data['debug'] = [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ];
        }

        return ApiResponse::error(
            'SERVER_ERROR',
            'Something went wrong. Please try again. If it keeps happening, share the reference with support.',
            500,
            data: $data,
        );
    }
}
