<?php

namespace Tests\Unit\Domain\Identity\Permissions;

use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Identity\Permissions\SystemRole;
use PHPUnit\Framework\TestCase;

/**
 * The catalogue must match SRS §3.3 exactly. These counts come from the
 * approved table, so a change here is a change to the requirements.
 */
class PermissionCatalogueTest extends TestCase
{
    public function test_it_has_every_srs_permission_once(): void
    {
        $this->assertCount(50, PermissionCatalogue::names());
        $this->assertSame(PermissionCatalogue::names(), array_values(array_unique(PermissionCatalogue::names())));
    }

    public function test_default_role_grants_match_the_srs_table(): void
    {
        $this->assertCount(48, PermissionCatalogue::defaultsFor(SystemRole::Owner));
        $this->assertCount(36, PermissionCatalogue::defaultsFor(SystemRole::Supervisor));
        $this->assertCount(18, PermissionCatalogue::defaultsFor(SystemRole::Cashier));
        $this->assertSame(PermissionCatalogue::LOW_RISK, PermissionCatalogue::defaultsFor(SystemRole::Employee));
    }

    public function test_the_owner_lacks_only_the_employee_only_actions(): void
    {
        $this->assertSame(
            ['execution.join', 'additional.request'],
            array_values(array_diff(PermissionCatalogue::names(), PermissionCatalogue::defaultsFor(SystemRole::Owner))),
        );
    }

    public function test_only_the_owner_manages_staff_roles_and_settings_by_default(): void
    {
        foreach ([SystemRole::Supervisor, SystemRole::Cashier, SystemRole::Employee] as $role) {
            $this->assertSame([], array_intersect(['user.manage', 'role.manage', 'settings.manage'], PermissionCatalogue::defaultsFor($role)), $role->value);
        }
    }

    public function test_every_permission_has_a_label_and_a_group(): void
    {
        foreach (PermissionCatalogue::describe() as $row) {
            $this->assertNotSame('', $row['label'], $row['name']);
            $this->assertNotSame('', $row['group'], $row['name']);
            $this->assertStringNotContainsString("\u{2014}", $row['label']);
        }
    }
}
