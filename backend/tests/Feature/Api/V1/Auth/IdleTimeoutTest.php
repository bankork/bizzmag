<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domain\Business\Models\BusinessSetting;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Tests\Feature\Api\V1\ApiTestCase;

/**
 * SEC-018: idle limits by role, enforced on the server.
 */
class IdleTimeoutTest extends ApiTestCase
{
    public function test_a_cashier_idle_for_29_minutes_is_still_signed_in(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier);

        $this->signIn($cashier);
        $this->travel(29)->minutes();

        $this->getJson('api/v1/me')->assertOk();
    }

    public function test_a_cashier_idle_for_more_than_30_minutes_is_signed_out_and_it_is_recorded(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier);

        $this->signIn($cashier);
        $this->travel(30)->minutes();
        $this->travel(1)->seconds();

        $this->getJson('api/v1/me')
            ->assertStatus(401)
            ->assertJsonPath('code', 'SESSION_IDLE')
            ->assertJsonPath('message', 'You were signed out after a period of inactivity. Please sign in again.');

        $this->assertGuest('web');
        $this->assertDatabaseHas('audit_events', ['action' => 'auth.session_expired', 'actor_id' => $cashier->id]);
    }

    public function test_activity_keeps_the_session_alive(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier);

        $this->signIn($cashier);
        $this->travel(20)->minutes();
        $this->getJson('api/v1/me')->assertOk();
        $this->travel(20)->minutes();

        $this->getJson('api/v1/me')->assertOk();
    }

    public function test_an_employee_only_user_stays_signed_in_for_days(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee);

        $this->signIn($employee);
        $this->travel(6)->days();

        $this->getJson('api/v1/me')->assertOk()->assertJsonPath('data.session.kind', 'personal');
    }

    public function test_an_employee_given_a_financial_permission_gets_the_short_limit(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], [SystemRole::Employee, SystemRole::Cashier]);

        $this->signIn($employee);
        $this->travel(31)->minutes();

        $this->getJson('api/v1/me')->assertStatus(401)->assertJsonPath('code', 'SESSION_IDLE');
    }

    public function test_limits_changed_in_settings_take_effect(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier);
        app(CurrentBusiness::class)->within($salon['business']->id, fn () => BusinessSetting::query()->create(['key' => 'sessions.idle_minutes_privileged', 'value' => 10]));

        $this->signIn($cashier);
        $this->travel(11)->minutes();

        $this->getJson('api/v1/me')->assertStatus(401)->assertJsonPath('code', 'SESSION_IDLE');
    }
}
