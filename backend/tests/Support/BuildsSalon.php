<?php

namespace Tests\Support;

use App\Domain\Business\Actions\InstallBusiness;
use App\Domain\Business\Models\Business;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Http\Middleware\EnforceIdleTimeout;
use App\Models\User;

/**
 * Builds an installed salon for API tests and signs people in the way the
 * web app does: cookie session from the web app origin.
 */
trait BuildsSalon
{
    public const WEB_APP_ORIGIN = 'http://localhost:3000';

    public const OWNER_PASSWORD = 'Owner-pass-2026';

    protected function setUpBuildsSalon(): void
    {
        // Requests from the web app origin get a session (Sanctum stateful).
        $this->withHeaders(['Origin' => self::WEB_APP_ORIGIN, 'Referer' => self::WEB_APP_ORIGIN.'/']);
    }

    /**
     * @return array{business: Business, owner: User}
     */
    protected function installSalon(string $name = 'Adunni Test Salon', string $ownerEmail = 'owner@salon.test'): array
    {
        $result = app(InstallBusiness::class)($name, [
            'name' => 'Salon Owner',
            'email' => $ownerEmail,
            'phone' => null,
            'password' => self::OWNER_PASSWORD,
        ], allowAdditional: true);

        app(CurrentBusiness::class)->set(null);

        return $result;
    }

    /**
     * A staff member in the business holding the given default roles.
     *
     * @param  SystemRole|list<SystemRole>  $roles
     * @param  array<string, mixed>  $attributes
     */
    protected function staffMember(Business $business, SystemRole|array $roles, array $attributes = [], bool $withProfile = true): User
    {
        return app(CurrentBusiness::class)->within($business->id, function () use ($business, $roles, $attributes, $withProfile): User {
            $user = User::factory()->create($attributes);
            $user->businesses()->attach($business->id, ['is_active' => true]);
            $user->syncRoles(array_map(fn (SystemRole $role) => $this->systemRole($business, $role), is_array($roles) ? $roles : [$roles]));

            if ($withProfile) {
                EmployeeProfile::query()->create(['user_id' => $user->id, 'display_name' => $user->name]);
            }

            return $user->refresh();
        });
    }

    protected function systemRole(Business $business, SystemRole $role): Role
    {
        return Role::query()->forBusiness($business->id)->where('system_key', $role->value)->firstOrFail();
    }

    /**
     * Sign in through the session guard, as after a real login.
     */
    protected function signIn(User $user): static
    {
        $this->actingAs($user, 'web');

        return $this->withSession([EnforceIdleTimeout::SESSION_KEY => now()->getTimestamp()]);
    }
}
