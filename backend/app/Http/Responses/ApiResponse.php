<?php

namespace App\Http\Responses;

use App\Domain\Shared\Idempotency\IdempotentResult;
use Illuminate\Http\JsonResponse;

/**
 * The standard API envelope (Stage 3 §8.1).
 *
 * Success: { "success": true, "data": ..., "meta": {...} }
 * Error:   { "success": false, "code": "...", "message": "...", "errors": {...}, "data": {...} }
 */
final class ApiResponse
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        $payload = ['success' => true, 'data' => $data];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        return response()->json($payload, $status);
    }

    /**
     * @param  array<string, list<string>>  $errors  Field errors.
     * @param  array<string, mixed>  $data  Safe context for the client, for example the new breakdown.
     */
    public static function error(string $code, string $message, int $status, array $errors = [], array $data = []): JsonResponse
    {
        $payload = ['success' => false, 'code' => $code, 'message' => $message];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        if ($data !== []) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    public static function fromIdempotent(IdempotentResult $result): JsonResponse
    {
        return self::success($result->body, status: $result->status)
            ->header('Idempotent-Replayed', $result->replayed ? 'true' : 'false');
    }
}
