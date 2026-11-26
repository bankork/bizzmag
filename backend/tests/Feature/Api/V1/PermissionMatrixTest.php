<?php

namespace Tests\Feature\Api\V1;

use App\Domain\Audit\AuditEvent;
use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Identity\Permissions\SystemRole;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\EndpointCatalogue;

/**
 * The permission matrix (SRS §3.3, FR-IAM-004, SEC-002, SEC-016): every
 * protected endpoint against every default role and a signed-out visitor.
 *
 * Allowed means the request got past authorisation (it may still fail
 * validation). Refused means 403 with no data, and an access.denied record.
 */
class PermissionMatrixTest extends ApiTestCase
{
    /**
     * @return array<string, array{0: string}>
     */
    public static function protectedEndpoints(): array
    {
        $cases = [];

        foreach (array_keys(EndpointCatalogue::protected()) as $name) {
            $cases[$name] = [$name];
        }

        return $cases;
    }

    #[DataProvider('protectedEndpoints')]
    public function test_each_default_role_is_allowed_or_refused_as_the_catalogue_says(string $name): void
    {
        [$method, $path, $permission] = EndpointCatalogue::ENDPOINTS[$name];
        $salon = $this->installSalon();
        $target = $this->staffMember($salon['business'], SystemRole::Employee);
        $people = [];

        foreach (SystemRole::cases() as $role) {
            $people[$role->value] = $role === SystemRole::Owner ? $salon['owner'] : $this->staffMember($salon['business'], $role);
        }

        $uri = 'api/v1/'.strtr($path, [
            '{user}' => (string) $target->id,
            '{role}' => (string) $this->systemRole($salon['business'], SystemRole::Employee)->id,
        ]);

        $this->json($method, $uri)->assertStatus(401);

        foreach ($people as $roleKey => $person) {
            $allowed = in_array($permission, PermissionCatalogue::defaultsFor(SystemRole::from($roleKey)), true);
            $response = $this->requestAs($person, $method, $uri);

            if ($allowed) {
                $this->assertNotContains($response->status(), [401, 403], "{$roleKey} should be allowed to call {$name}, got {$response->status()}.");

                continue;
            }

            $response->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN')->assertJsonMissingPath('data');
            $this->assertTrue(
                AuditEvent::query()->where('action', 'access.denied')->where('actor_id', $person->id)->get()
                    ->contains(fn (AuditEvent $event) => ($event->after['permission'] ?? null) === $permission),
                "The refusal of {$name} for {$roleKey} was not recorded.",
            );
        }
    }

    public function test_an_employee_refused_an_admin_action_changes_nothing(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee);

        $this->signIn($employee)->patchJson('api/v1/settings/tax', ['version' => 1, 'enabled' => true, 'name' => 'VAT', 'rate_bp' => 750, 'mode' => 'exclusive'])
            ->assertStatus(403);

        $this->assertDatabaseHas('tax_settings', ['business_id' => $salon['business']->id, 'enabled' => false, 'version' => 1]);
        $event = AuditEvent::query()->where('action', 'access.denied')->sole();
        $this->assertSame($employee->id, $event->actor_id);
        $this->assertSame($salon['business']->id, $event->business_id);
        $this->assertEquals(['permission' => 'settings.manage', 'method' => 'PATCH', 'path' => '/api/v1/settings/tax', 'route' => 'api.v1.settings.tax.update'], $event->after);
    }

    public function test_every_default_role_can_reach_its_own_session_endpoints(): void
    {
        $salon = $this->installSalon();

        foreach (SystemRole::cases() as $role) {
            $person = $role === SystemRole::Owner ? $salon['owner'] : $this->staffMember($salon['business'], $role);

            $this->requestAs($person, 'GET', 'api/v1/me')->assertOk();
        }
    }

    private function requestAs(User $person, string $method, string $uri): TestResponse
    {
        $this->flushSession();
        $this->app['auth']->forgetGuards();

        return $this->signIn($person)->json($method, $uri);
    }
}
