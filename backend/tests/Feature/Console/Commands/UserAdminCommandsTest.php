<?php

namespace Tests\Feature\Console\Commands;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Identity\Permissions\SystemRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Api\V1\ApiTestCase;

class UserAdminCommandsTest extends ApiTestCase
{
    public function test_reset_password_gives_a_temporary_password_that_must_be_changed(): void
    {
        $salon = $this->installSalon();

        $this->artisan('app:user:reset-password', ['identifier' => 'OWNER@salon.test'])
            ->expectsOutputToContain('Temporary password for Salon Owner: Temp-')
            ->assertSuccessful();

        $owner = $salon['owner']->refresh();
        $this->assertTrue($owner->must_change_password);
        $this->assertFalse(Hash::check(self::OWNER_PASSWORD, $owner->password));
        $this->assertDatabaseHas('audit_events', ['action' => 'auth.password_reset', 'subject_id' => $owner->id, 'actor_id' => null]);
    }

    public function test_reset_password_for_an_unknown_account_fails(): void
    {
        $this->artisan('app:user:reset-password', ['identifier' => 'nobody@salon.test'])
            ->expectsOutput('No account uses that email or phone number.')
            ->assertFailed();
    }

    public function test_sync_adds_missing_permissions_and_restores_the_owner_role_without_touching_edited_roles(): void
    {
        $salon = $this->installSalon();
        $owner = $this->systemRole($salon['business'], SystemRole::Owner);
        $cashier = $this->systemRole($salon['business'], SystemRole::Cashier);
        $cashier->revokePermissionTo('payment.record');
        DB::table('role_has_permissions')->where('role_id', $owner->id)->delete();
        Permission::query()->where('name', 'import.run')->delete();

        $this->artisan('app:permissions:sync')->expectsOutput('Permissions are up to date for 1 business(es).')->assertSuccessful();

        $this->assertSame(count(PermissionCatalogue::names()), Permission::query()->count());
        $this->assertCount(count(PermissionCatalogue::defaultsFor(SystemRole::Owner)), Role::query()->findOrFail($owner->id)->permissions);
        $this->assertFalse(Role::query()->findOrFail($cashier->id)->hasPermissionTo('payment.record'));
    }
}
