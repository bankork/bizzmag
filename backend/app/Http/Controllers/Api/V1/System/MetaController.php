<?php

namespace App\Http\Controllers\Api\V1\System;

use App\Domain\Shared\Money\Money;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Public, non-sensitive API metadata. Used by the frontend to confirm it can
 * reach the API, and by the deployment smoke tests.
 */
class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return ApiResponse::success([
            'name' => config('app.name'),
            'api_version' => 'v1',
            'release' => config('bizzmag.version'),
            'currency' => Money::CURRENCY,
            'server_time' => now()->toIso8601ZuluString(),
        ]);
    }
}
