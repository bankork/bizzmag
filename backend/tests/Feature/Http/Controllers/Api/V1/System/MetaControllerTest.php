<?php

namespace Tests\Feature\Http\Controllers\Api\V1\System;

use Tests\TestCase;

class MetaControllerTest extends TestCase
{
    public function test_returns_public_api_metadata_in_the_success_envelope(): void
    {
        $this->travelTo('2026-11-25 10:14:03');

        $response = $this->getJson('api/v1/meta');

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.api_version', 'v1');
        $response->assertJsonPath('data.currency', 'NGN');
        $response->assertJsonPath('data.server_time', '2026-11-25T10:14:03Z');
    }
}
