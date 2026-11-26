<?php

namespace Tests\Feature\Api\V1\Identity;

use App\Domain\Audit\AuditEvent;
use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Identity\Permissions\SystemRole;
use Tests\Feature\Api\V1\ApiTestCase;

class RoleManagementTest extends ApiTestCase
{
    public function test_the_roles_screen_lists_the_four_default_roles_first(): void
    {
        $salon = $this->installSalon();

        $response = $this->signIn($salon['owner'])->getJson('api/v1/roles')->assertOk();

        $this->assertSame(['owner', 'supervisor', 'cashier', 'employee'], array_column($response->json('data'), 'system_key'));
        $response->assertJsonPath('data.0.users_count', 1)
            ->assertJsonPath('data.2.discount_cap_bp', 1000)
            ->assertJsonPath('data.2.discount_cap_kobo', 500000)
            ->assertJsonPath('data.0.discount_cap_bp', null);
    }

    public function test_the_permission_catalogue_is_in_plain_english(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->getJson('api/v1/permissions')
            ->assertOk()
            ->assertJsonCount(count(PermissionCatalogue::names()), 'data')
            ->assertJsonPath('data.0', ['name' => 'user.manage', 'group' => 'Staff and access', 'label' => 'Create, edit and disable staff accounts']);
    }

    public function test_the_owner_creates_a_custom_role_and_it_is_audited(): void
    {
        $salon = $this->installSalon();

        $response = $this->signIn($salon['owner'])->postJson('api/v1/roles', [
            'name' => 'Senior stylist',
            'description' => 'Braiders who can give small discounts',
            'discount_cap_bp' => 500,
            'discount_cap_kobo' => 200000,
            'permissions' => ['execution.submit', 'execution.join', 'discount.apply', 'report.own'],
        ]);

        $response->assertCreated()->assertJsonPath('data.is_system', false)->assertJsonPath('data.users_count', 0);
        $event = AuditEvent::query()->where('action', 'role.created')->sole();
        $this->assertNull($event->before);
        $this->assertSame(['discount.apply', 'execution.join', 'execution.submit', 'report.own'], $event->after['permissions']);
    }

    public function test_changing_a_role_records_before_and_after(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->systemRole($salon['business'], SystemRole::Cashier);

        $this->signIn($salon['owner'])->patchJson("api/v1/roles/{$cashier->id}", ['discount_cap_bp' => 1500])->assertOk();

        $event = AuditEvent::query()->where('action', 'role.updated')->sole();
        $this->assertSame(1000, $event->before['discount_cap_bp']);
        $this->assertSame(1500, $event->after['discount_cap_bp']);
    }

    public function test_the_owner_role_permissions_cannot_be_changed(): void
    {
        $salon = $this->installSalon();
        $owner = $this->systemRole($salon['business'], SystemRole::Owner);

        $this->signIn($salon['owner'])->patchJson("api/v1/roles/{$owner->id}", ['permissions' => ['report.own']])
            ->assertStatus(409)
            ->assertJsonPath('code', 'SYSTEM_ROLE_PROTECTED');

        $this->assertEqualsCanonicalizing(PermissionCatalogue::defaultsFor(SystemRole::Owner), $owner->refresh()->permissions->pluck('name')->all());
    }

    public function test_removing_staff_management_from_the_only_administrators_role_is_refused(): void
    {
        $salon = $this->installSalon();
        $supervisor = $this->systemRole($salon['business'], SystemRole::Supervisor);
        $supervisor->givePermissionTo(['user.manage', 'role.manage']);
        $admin = $this->staffMember($salon['business'], SystemRole::Supervisor);
        $salon['owner']->forceFill(['is_active' => false])->save();

        $this->signIn($admin)->patchJson("api/v1/roles/{$supervisor->id}", ['permissions' => PermissionCatalogue::defaultsFor(SystemRole::Supervisor)])
            ->assertStatus(409)
            ->assertJsonPath('code', 'LAST_ADMINISTRATOR');

        $this->assertTrue($supervisor->refresh()->hasPermissionTo('user.manage'));
    }

    public function test_default_roles_cannot_be_deleted(): void
    {
        $salon = $this->installSalon();
        $employee = $this->systemRole($salon['business'], SystemRole::Employee);

        $this->signIn($salon['owner'])->deleteJson("api/v1/roles/{$employee->id}")
            ->assertStatus(409)
            ->assertJsonPath('code', 'SYSTEM_ROLE_PROTECTED');
    }

    public function test_a_custom_role_in_use_cannot_be_deleted_until_nobody_has_it(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee);
        $this->signIn($salon['owner']);
        $roleId = $this->postJson('api/v1/roles', ['name' => 'Trainee', 'permissions' => ['report.own']])->json('data.id');
        $this->putJson("api/v1/users/{$employee->id}/roles", ['role_ids' => [$roleId]])->assertOk();

        $this->deleteJson("api/v1/roles/{$roleId}")->assertStatus(409)->assertJsonPath('code', 'ROLE_IN_USE');

        $this->putJson("api/v1/users/{$employee->id}/roles", ['role_ids' => [$this->systemRole($salon['business'], SystemRole::Employee)->id]])->assertOk();
        $this->deleteJson("api/v1/roles/{$roleId}")->assertOk();
        $this->assertDatabaseHas('audit_events', ['action' => 'role.deleted', 'subject_id' => $roleId]);
    }

    public function test_unknown_permissions_and_duplicate_names_are_refused(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->postJson('api/v1/roles', ['name' => 'Supervisor', 'permissions' => ['money.print']])
            ->assertStatus(422)
            ->assertJsonPath('errors.name.0', 'Another role already has this name.')
            ->assertJsonValidationErrors(['permissions.0'], 'errors');
    }

    public function test_with_several_roles_the_highest_discount_cap_applies(): void
    {
        $salon = $this->installSalon();
        $user = $this->staffMember($salon['business'], [SystemRole::Cashier, SystemRole::Supervisor]);

        $this->signIn($user)->getJson('api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.discount_cap', ['percent_bp' => 3000, 'per_invoice_kobo' => null]);
    }
}
