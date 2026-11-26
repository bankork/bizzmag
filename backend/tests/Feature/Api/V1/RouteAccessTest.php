<?php

namespace Tests\Feature\Api\V1;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as Router;
use Tests\Support\EndpointCatalogue;
use Tests\TestCase;

/**
 * Every /api/v1 route must say who may call it. A new route without a
 * permission, or missing from the endpoint catalogue, fails the build.
 */
class RouteAccessTest extends TestCase
{
    public function test_every_api_route_is_in_the_endpoint_catalogue(): void
    {
        $missing = array_diff(array_keys($this->apiRoutes()), array_keys(EndpointCatalogue::ENDPOINTS));
        $stale = array_diff(array_keys(EndpointCatalogue::ENDPOINTS), array_keys($this->apiRoutes()));

        $this->assertSame([], array_values($missing), 'Add these routes to tests/Support/EndpointCatalogue.php.');
        $this->assertSame([], array_values($stale), 'These catalogue entries have no route.');
    }

    public function test_every_route_enforces_the_access_the_catalogue_declares(): void
    {
        $problems = [];

        foreach ($this->apiRoutes() as $name => $route) {
            [$method, $path, $access] = EndpointCatalogue::ENDPOINTS[$name] ?? [null, null, null];
            $middleware = $route->gatherMiddleware();
            $signedIn = in_array('signed-in', $middleware, true);
            $permissions = array_values(array_filter($middleware, fn ($entry) => is_string($entry) && str_starts_with($entry, 'permission:')));
            $ownerOnly = in_array('owner', $middleware, true);

            if ($ownerOnly !== in_array($name, EndpointCatalogue::OWNER_ONLY, true)) {
                $problems[] = "{$name}: owner-only does not match the catalogue";
            }

            if ($method !== null && ! in_array($method, $route->methods(), true)) {
                $problems[] = "{$name}: method is not {$method}";
            }

            if ($path !== null && $route->uri() !== 'api/v1/'.$path) {
                $problems[] = "{$name}: path is {$route->uri()}";
            }

            $expected = match ($access) {
                EndpointCatalogue::PUBLIC => ! $signedIn && $permissions === [],
                EndpointCatalogue::SIGNED_IN => $signedIn && $permissions === [],
                null => false,
                default => $signedIn && $permissions === ["permission:{$access}"],
            };

            if (! $expected) {
                $problems[] = "{$name}: expected {$access}";
            }
        }

        $this->assertSame([], $problems);
    }

    /**
     * @return array<string, Route>
     */
    private function apiRoutes(): array
    {
        $routes = [];

        foreach (Router::getRoutes()->getRoutes() as $route) {
            $name = (string) $route->getName();

            if (str_starts_with($name, 'api.v1.')) {
                $routes[substr($name, strlen('api.v1.'))] = $route;
            }
        }

        return $routes;
    }
}
