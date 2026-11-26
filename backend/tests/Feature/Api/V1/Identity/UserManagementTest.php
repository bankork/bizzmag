<?php

namespace Tests\Feature\Api\V1\Identity;

use App\Domain\Audit\AuditEvent;
use App\Domain\Identity\Permissions\SystemRole;
use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;

class UserManagementTest extends ApiTestCase
{
    public function test_the_owner_creates_a_cashier_who_can_then_sign_in_and_must_choose_a_password(): void
    {
        $salon = $this->installSalon();
        $cashierRole = $this->systemRole($salon['business'], SystemRole::Cashier);

        $created = $this->signIn($salon['owner'])->postJson('api/v1/users', [
            'name' => 'Kemi Adeyemi',
            'phone' => '0806 000 1917',
            'password' => 'Temp-pass-2026',
            'role_ids' => [$cashierRole->id],
        ]);

        $created->assertCreated()
            ->assertJsonPath('data.phone', '+2348060001917')
            ->assertJsonPath('data.email', null)
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonPath('data.roles.0.name', 'Front desk / Cashier')
            ->assertJsonPath('data.employee_profile', null);
        $this->assertDatabaseHas('audit_events', ['action' => 'user.created', 'subject_id' => $created->json('data.id'), 'actor_id' => $salon['owner']->id]);

        $this->postJson('api/v1/auth/logout');
        $this->postJson('api/v1/auth/login', ['identifier' => '+234 806 000 1917', 'password' => 'Temp-pass-2026'])
            ->assertOk()
            ->assertJsonPath('data.user.must_change_password', true)
            ->assertJsonPath('data.home', 'desk');
    }

    public function test_the_owner_creates_an_employee_with_a_profile_for_salon_work(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->postJson('api/v1/users', [
            'name' => 'Chiamaka Eze',
            'email' => 'Chiamaka@Salon.test',
            'password' => 'Temp-pass-2026',
            'role_ids' => [$this->systemRole($salon['business'], SystemRole::Employee)->id],
            'employee_profile' => ['display_name' => 'Chiamaka', 'job_title' => 'Braider'],
        ])->assertCreated()
            ->assertJsonPath('data.email', 'chiamaka@salon.test')
            ->assertJsonPath('data.employee_profile.display_name', 'Chiamaka')
            ->assertJsonPath('data.employee_profile.is_active', true);
    }

    public function test_a_new_user_needs_an_email_or_a_phone_and_at_least_one_role(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->postJson('api/v1/users', ['name' => 'No Contact', 'password' => 'Temp-pass-2026', 'role_ids' => []])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Enter an email or a phone number.')
            ->assertJsonPath('errors.role_ids.0', 'Choose at least one role.');
    }

    public function test_the_same_phone_number_written_differently_is_still_a_duplicate(): void
    {
        $salon = $this->installSalon();
        $this->staffMember($salon['business'], SystemRole::Employee, ['phone' => '+2348031234567']);

        $this->signIn($salon['owner'])->postJson('api/v1/users', [
            'name' => 'Copy Cat',
            'phone' => '08031234567',
            'password' => 'Temp-pass-2026',
            'role_ids' => [$this->systemRole($salon['business'], SystemRole::Employee)->id],
        ])->assertStatus(422)->assertJsonPath('errors.phone.0', 'Someone already uses this phone number.');
    }

    public function test_an_invalid_phone_number_is_refused(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->postJson('api/v1/users', [
            'name' => 'Bad Number',
            'phone' => '0803 12',
            'password' => 'Temp-pass-2026',
            'role_ids' => [$this->systemRole($salon['business'], SystemRole::Employee)->id],
        ])->assertStatus(422)->assertJsonPath('errors.phone.0', 'Enter a valid phone number, for example 0803 123 4567.');
    }

    public function test_editing_a_user_is_audited_with_before_and_after(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee, ['name' => 'Blessing Udoh']);

        $this->signIn($salon['owner'])->patchJson("api/v1/users/{$employee->id}", [
            'name' => 'Blessing Udoh-Akpan',
            'employee_profile' => ['job_title' => 'Senior braider'],
        ])->assertOk()->assertJsonPath('data.employee_profile.job_title', 'Senior braider');

        $event = AuditEvent::query()->where('action', 'user.updated')->where('subject_id', $employee->id)->sole();
        $this->assertSame('Blessing Udoh', $event->before['name']);
        $this->assertSame('Blessing Udoh-Akpan', $event->after['name']);
        $this->assertSame('Senior braider', $event->after['employee_profile']['job_title']);
    }

    public function test_a_user_must_keep_an_email_or_a_phone(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee, ['email' => 'efe@salon.test', 'phone' => null]);

        $this->signIn($salon['owner'])->patchJson("api/v1/users/{$employee->id}", ['email' => null])
            ->assertStatus(422)
            ->assertJsonPath('errors.email.0', 'Keep an email or a phone number so they can sign in.');
    }

    public function test_the_owner_cannot_disable_themselves(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->postJson("api/v1/users/{$salon['owner']->id}/disable")
            ->assertStatus(422)
            ->assertJsonPath('code', 'SELF_ACTION_NOT_ALLOWED');
    }

    public function test_the_last_person_who_can_manage_staff_cannot_be_disabled(): void
    {
        $salon = $this->installSalon();
        $secondOwner = $this->staffMember($salon['business'], SystemRole::Owner);

        $this->signIn($secondOwner)->postJson("api/v1/users/{$salon['owner']->id}/disable")->assertOk();

        $this->assertSame(1, User::query()->where('is_active', true)->whereKey([$salon['owner']->id, $secondOwner->id])->count());
    }

    public function test_removing_the_owner_role_from_the_only_owner_is_refused(): void
    {
        $salon = $this->installSalon();
        $supervisor = $this->systemRole($salon['business'], SystemRole::Supervisor);

        $this->signIn($salon['owner'])->putJson("api/v1/users/{$salon['owner']->id}/roles", ['role_ids' => [$supervisor->id]])
            ->assertStatus(409)
            ->assertJsonPath('code', 'LAST_ADMINISTRATOR');

        $this->assertTrue($salon['owner']->refresh()->hasRole($this->systemRole($salon['business'], SystemRole::Owner)));
    }

    public function test_a_user_can_hold_several_roles_and_the_change_is_audited(): void
    {
        $salon = $this->installSalon();
        $ngozi = $this->staffMember($salon['business'], SystemRole::Employee);
        $roleIds = [
            $this->systemRole($salon['business'], SystemRole::Supervisor)->id,
            $this->systemRole($salon['business'], SystemRole::Employee)->id,
        ];

        $this->signIn($salon['owner'])->putJson("api/v1/users/{$ngozi->id}/roles", ['role_ids' => $roleIds])
            ->assertOk()
            ->assertJsonCount(2, 'data.roles');

        $event = AuditEvent::query()->where('action', 'user.roles_changed')->sole();
        $this->assertSame(['Employee'], $event->before['roles']);
        $this->assertSame(['Employee', 'Supervisor'], $event->after['roles']);
    }

    public function test_a_reset_password_signs_the_user_out_and_forces_a_change(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier);

        $this->signIn($salon['owner'])->postJson("api/v1/users/{$cashier->id}/reset-password", ['password' => 'Temp-pass-2026'])
            ->assertOk()
            ->assertJsonPath('data.must_change_password', true);

        $this->assertDatabaseHas('audit_events', ['action' => 'auth.password_reset', 'subject_id' => $cashier->id, 'actor_id' => $salon['owner']->id]);
        $this->assertStringNotContainsString('Temp-pass-2026', (string) json_encode(AuditEvent::query()->get()->toArray()));
    }

    public function test_a_disabled_user_can_be_enabled_again(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee, ['is_active' => false]);

        $this->signIn($salon['owner'])->postJson("api/v1/users/{$employee->id}/enable")
            ->assertOk()
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.disabled_at', null);
    }

    public function test_the_staff_list_can_be_searched_and_filtered(): void
    {
        $salon = $this->installSalon();
        $this->staffMember($salon['business'], SystemRole::Employee, ['name' => 'Zainab Musa']);
        $this->staffMember($salon['business'], SystemRole::Employee, ['name' => 'Efe Omoregie', 'is_active' => false]);

        $this->signIn($salon['owner']);

        $this->getJson('api/v1/users?filter[q]=zain')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Zainab Musa');
        $this->getJson('api/v1/users?filter[status]=disabled')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Efe Omoregie');
        $this->getJson('api/v1/users')->assertOk()->assertJsonPath('meta.pagination.total', 3);
    }
}
