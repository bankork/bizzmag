<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domain\Audit\AuditEvent;
use App\Domain\Identity\Permissions\SystemRole;
use App\Models\User;
use Tests\Feature\Api\V1\ApiTestCase;

class SignInTest extends ApiTestCase
{
    private const PASSWORD = 'Kemi-cashier-26';

    public function test_a_cashier_signs_in_with_their_email_and_lands_on_the_front_desk(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier, ['email' => 'kemi@salon.test', 'password' => self::PASSWORD]);

        $response = $this->postJson('api/v1/auth/login', ['identifier' => 'Kemi@Salon.test', 'password' => self::PASSWORD]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $cashier->id)
            ->assertJsonPath('data.business.id', $salon['business']->id)
            ->assertJsonPath('data.home', 'desk')
            ->assertJsonPath('data.session.kind', 'privileged')
            ->assertJsonPath('data.session.idle_timeout_seconds', 1800)
            ->assertJsonPath('data.discount_cap', ['percent_bp' => 1000, 'per_invoice_kobo' => 500000]);
        $this->assertContains('payment.record', $response->json('data.permissions'));
        $this->assertNotContains('user.manage', $response->json('data.permissions'));
        $this->assertAuthenticatedAs($cashier, 'web');
        $this->assertDatabaseHas('audit_events', ['action' => 'auth.login', 'actor_id' => $cashier->id, 'business_id' => $salon['business']->id]);
    }

    public function test_staff_can_sign_in_with_a_phone_number_written_the_local_way(): void
    {
        $salon = $this->installSalon();
        $employee = $this->staffMember($salon['business'], SystemRole::Employee, ['email' => null, 'phone' => '+2348031234567', 'password' => self::PASSWORD]);

        $response = $this->postJson('api/v1/auth/login', ['identifier' => '0803 123 4567', 'password' => self::PASSWORD]);

        $response->assertOk()
            ->assertJsonPath('data.user.id', $employee->id)
            ->assertJsonPath('data.home', 'staff')
            ->assertJsonPath('data.session.kind', 'personal')
            ->assertJsonPath('data.session.idle_timeout_seconds', 7 * 86400);
    }

    public function test_a_wrong_password_and_an_unknown_account_get_the_same_answer(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier, ['email' => 'kemi@salon.test', 'password' => self::PASSWORD]);

        $wrongPassword = $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => 'not-her-password']);
        $unknown = $this->postJson('api/v1/auth/login', ['identifier' => 'nobody@salon.test', 'password' => 'not-her-password']);

        $wrongPassword->assertStatus(422)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $unknown->assertStatus(422)->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $this->assertSame($wrongPassword->json('message'), $unknown->json('message'));
        $this->assertGuest('web');
        $this->assertDatabaseHas('audit_events', ['action' => 'auth.login_failed', 'subject_id' => $cashier->id]);
    }

    public function test_the_fifth_failure_locks_that_account_from_that_address_for_fifteen_minutes(): void
    {
        $salon = $this->installSalon();
        $this->staffMember($salon['business'], SystemRole::Cashier, ['email' => 'kemi@salon.test', 'password' => self::PASSWORD]);

        foreach (range(1, 4) as $attempt) {
            $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => 'wrong'])->assertStatus(422);
        }

        $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => 'wrong'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'LOGIN_LOCKED')
            ->assertJsonPath('data.retry_after_seconds', 900);

        $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => self::PASSWORD])
            ->assertStatus(429);
        $this->assertDatabaseHas('audit_events', ['action' => 'auth.locked']);

        $this->travel(15)->minutes();
        $this->travel(1)->seconds();

        $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => self::PASSWORD])->assertOk();
    }

    public function test_a_lock_from_one_address_does_not_block_the_same_account_elsewhere(): void
    {
        $salon = $this->installSalon();
        $this->staffMember($salon['business'], SystemRole::Cashier, ['email' => 'kemi@salon.test', 'password' => self::PASSWORD]);

        foreach (range(1, 5) as $attempt) {
            $this->withServerVariables(['REMOTE_ADDR' => '197.210.1.1'])
                ->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => 'wrong']);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '102.89.2.2'])
            ->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => self::PASSWORD])
            ->assertOk();
    }

    public function test_twenty_failures_from_one_address_across_accounts_lock_the_address(): void
    {
        $this->installSalon();

        foreach (range(1, 19) as $attempt) {
            $this->postJson('api/v1/auth/login', ['identifier' => "guess{$attempt}@salon.test", 'password' => 'wrong'])->assertStatus(422);
        }

        $this->postJson('api/v1/auth/login', ['identifier' => 'guess20@salon.test', 'password' => 'wrong'])->assertStatus(429);
        $this->postJson('api/v1/auth/login', ['identifier' => 'owner@salon.test', 'password' => self::OWNER_PASSWORD])
            ->assertStatus(429)
            ->assertJsonPath('code', 'LOGIN_LOCKED');
    }

    public function test_a_successful_sign_in_clears_earlier_failures(): void
    {
        $salon = $this->installSalon();
        $this->staffMember($salon['business'], SystemRole::Cashier, ['email' => 'kemi@salon.test', 'password' => self::PASSWORD]);

        foreach (range(1, 4) as $attempt) {
            $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => 'wrong']);
        }
        $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => self::PASSWORD])->assertOk();
        $this->postJson('api/v1/auth/logout')->assertOk();

        $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@salon.test', 'password' => 'wrong'])->assertStatus(422);
    }

    public function test_a_disabled_user_is_told_so_only_after_giving_the_right_password(): void
    {
        $salon = $this->installSalon();
        $this->staffMember($salon['business'], SystemRole::Employee, ['email' => 'precious@salon.test', 'password' => self::PASSWORD, 'is_active' => false]);

        $this->postJson('api/v1/auth/login', ['identifier' => 'precious@salon.test', 'password' => 'wrong'])
            ->assertJsonPath('code', 'INVALID_CREDENTIALS');
        $this->postJson('api/v1/auth/login', ['identifier' => 'precious@salon.test', 'password' => self::PASSWORD])
            ->assertStatus(403)
            ->assertJsonPath('code', 'ACCOUNT_DISABLED');

        $this->assertGuest('web');
    }

    public function test_a_user_who_belongs_to_no_salon_cannot_sign_in(): void
    {
        User::factory()->create(['email' => 'stray@salon.test', 'password' => self::PASSWORD]);

        $this->postJson('api/v1/auth/login', ['identifier' => 'stray@salon.test', 'password' => self::PASSWORD])
            ->assertStatus(403)
            ->assertJsonPath('code', 'NO_BUSINESS_ACCESS');
        $this->assertGuest('web');
    }

    public function test_sign_in_requires_both_fields(): void
    {
        $this->postJson('api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('errors.identifier.0', 'Enter your email or phone number.')
            ->assertJsonPath('errors.password.0', 'Enter your password.');
    }

    public function test_signing_out_ends_the_session_and_is_audited(): void
    {
        $salon = $this->installSalon();

        $this->signIn($salon['owner'])->postJson('api/v1/auth/logout')->assertOk();

        $this->assertGuest('web');
        $this->assertTrue(AuditEvent::query()->where('action', 'auth.logout')->where('actor_id', $salon['owner']->id)->exists());
    }

    public function test_me_requires_a_signed_in_user(): void
    {
        $this->getJson('api/v1/me')->assertStatus(401)->assertJsonPath('code', 'UNAUTHENTICATED');
    }
}
