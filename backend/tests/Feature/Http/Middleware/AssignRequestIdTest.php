<?php

namespace Tests\Feature\Http\Middleware;

use Tests\TestCase;

class AssignRequestIdTest extends TestCase
{
    public function test_every_response_carries_a_generated_request_id(): void
    {
        $response = $this->getJson('api/v1/meta');

        $response->assertOk();
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', (string) $response->headers->get('X-Request-Id'));
    }

    public function test_reuses_a_safe_client_request_id(): void
    {
        $response = $this->getJson('api/v1/meta', ['X-Request-Id' => 'desk-tablet-0001']);

        $response->assertHeader('X-Request-Id', 'desk-tablet-0001');
    }

    public function test_replaces_an_unsafe_client_request_id(): void
    {
        $response = $this->getJson('api/v1/meta', ['X-Request-Id' => "bad id\nInjected: header"]);

        $this->assertNotSame("bad id\nInjected: header", $response->headers->get('X-Request-Id'));
    }
}
