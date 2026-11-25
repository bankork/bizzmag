<?php

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\RequireIdempotencyKey;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RequireIdempotencyKeyTest extends TestCase
{
    public function test_returns_422_when_the_key_is_missing(): void
    {
        Route::post('api/v1/_test/pay', fn () => ['ok' => true])->middleware('idempotent');

        $response = $this->postJson('api/v1/_test/pay');

        $response->assertUnprocessable();
        $response->assertJsonPath('code', 'IDEMPOTENCY_KEY_REQUIRED');
    }

    #[TestWith(['short'])]
    #[TestWith(['has spaces in it'])]
    public function test_returns_422_when_the_key_is_malformed(string $key): void
    {
        Route::post('api/v1/_test/pay', fn () => ['ok' => true])->middleware('idempotent');

        $response = $this->postJson('api/v1/_test/pay', [], ['Idempotency-Key' => $key]);

        $response->assertUnprocessable();
    }

    public function test_passes_a_valid_key_through_to_the_request(): void
    {
        Route::post('api/v1/_test/pay', fn (Request $request) => ['key' => $request->attributes->get(RequireIdempotencyKey::ATTRIBUTE)])
            ->middleware('idempotent');

        $response = $this->postJson('api/v1/_test/pay', [], ['Idempotency-Key' => '3f1c9a02-51be-4c1d-9b77-0a1d2e3f4a5b']);

        $response->assertOk();
        $response->assertExactJson(['key' => '3f1c9a02-51be-4c1d-9b77-0a1d2e3f4a5b']);
    }
}
