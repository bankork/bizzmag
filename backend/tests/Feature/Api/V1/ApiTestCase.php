<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\Support\BuildsSalon;
use Tests\TestCase;

/**
 * Base for /api/v1 feature tests: a clean database, requests from the web
 * app origin, and salon builders.
 */
abstract class ApiTestCase extends TestCase
{
    use BuildsSalon;
    use LazilyRefreshDatabase;
}
