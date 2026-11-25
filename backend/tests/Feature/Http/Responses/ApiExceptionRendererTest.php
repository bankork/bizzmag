<?php

namespace Tests\Feature\Http\Responses;

use App\Domain\Billing\Exceptions\InvalidDiscount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ApiExceptionRendererTest extends TestCase
{
    public function test_a_domain_error_returns_its_code_status_and_context(): void
    {
        Route::get('api/v1/_test/domain-error', fn () => throw new InvalidDiscount('A line discount is larger than the line value.', ['line' => 'braids']));

        $response = $this->getJson('api/v1/_test/domain-error');

        $response->assertUnprocessable();
        $response->assertExactJson([
            'success' => false,
            'code' => 'DISCOUNT_INVALID',
            'message' => 'A line discount is larger than the line value.',
            'data' => ['line' => 'braids'],
        ]);
    }

    public function test_a_validation_failure_returns_422_with_field_errors(): void
    {
        Route::post('api/v1/_test/validate', fn (Request $request) => $request->validate(['amount_kobo' => ['required', 'integer']]));

        $response = $this->postJson('api/v1/_test/validate', []);

        $response->assertUnprocessable();
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('code', 'VALIDATION_FAILED');
        $response->assertJsonPath('errors.amount_kobo.0', 'The amount kobo field is required.');
    }

    public function test_an_unknown_api_route_returns_404_in_the_envelope(): void
    {
        $response = $this->getJson('api/v1/does-not-exist');

        $response->assertNotFound();
        $response->assertExactJson([
            'success' => false,
            'code' => 'NOT_FOUND',
            'message' => 'The record was not found.',
        ]);
    }

    public function test_an_unexpected_error_returns_500_without_internal_details_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config(['app.debug' => false]);
        Route::get('api/v1/_test/crash', fn () => throw new RuntimeException('SQLSTATE secret table name'));

        $response = $this->getJson('api/v1/_test/crash');

        $response->assertInternalServerError();
        $response->assertJsonPath('code', 'SERVER_ERROR');
        $response->assertJsonMissingPath('data.debug');
        $this->assertStringNotContainsString('SQLSTATE', $response->getContent());
        $this->assertSame($response->headers->get('X-Request-Id'), $response->json('data.reference'));
    }
}
