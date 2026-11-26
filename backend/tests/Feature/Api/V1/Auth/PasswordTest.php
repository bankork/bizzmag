<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Domain\Identity\Permissions\SystemRole;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\Api\V1\ApiTestCase;

class PasswordTest extends ApiTestCase
{
    private const CURRENT = 'Current-pass-26';

    public function test_a_user_changes_their_own_password(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier, ['password' => self::CURRENT]);

        $this->signIn($cashier)->putJson('api/v1/me/password', [
            'current_password' => self::CURRENT,
            'password' => 'Brand-new-pass-1',
            'password_confirmation' => 'Brand-new-pass-1',
        ])->assertOk()->assertJsonPath('data.user.must_change_password', false);

        $this->assertTrue(Hash::check('Brand-new-pass-1', $cashier->refresh()->password));
        $this->assertDatabaseHas('audit_events', ['action' => 'auth.password_changed', 'subject_id' => $cashier->id]);
    }

    public function test_the_current_password_must_be_right(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier, ['password' => self::CURRENT]);

        $this->signIn($cashier)->putJson('api/v1/me/password', [
            'current_password' => 'not-it',
            'password' => 'Brand-new-pass-1',
            'password_confirmation' => 'Brand-new-pass-1',
        ])->assertStatus(422)->assertJsonPath('code', 'INCORRECT_PASSWORD');

        $this->assertTrue(Hash::check(self::CURRENT, $cashier->refresh()->password));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function weakPasswords(): array
    {
        return [
            'shorter than 8 characters' => ['Ab1-xyz', 'The password field must be at least 8 characters.'],
            'a commonly guessed password' => ['password123', 'This password is too easy to guess. Choose another.'],
            'a salon word' => ['Salon123', 'This password is too easy to guess. Choose another.'],
        ];
    }

    #[DataProvider('weakPasswords')]
    public function test_weak_passwords_are_refused(string $password, string $message): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier, ['password' => self::CURRENT]);

        $this->signIn($cashier)->putJson('api/v1/me/password', [
            'current_password' => self::CURRENT,
            'password' => $password,
            'password_confirmation' => $password,
        ])->assertStatus(422)->assertJsonPath('errors.password.0', $message);
    }

    public function test_after_a_reset_the_user_must_choose_a_new_password_before_anything_else(): void
    {
        $salon = $this->installSalon();
        $cashier = $this->staffMember($salon['business'], SystemRole::Cashier, ['password' => self::CURRENT, 'must_change_password' => true]);

        $this->signIn($cashier);

        $this->getJson('api/v1/me')->assertOk()->assertJsonPath('data.user.must_change_password', true);
        $this->getJson('api/v1/users')->assertStatus(403)->assertJsonPath('code', 'PASSWORD_CHANGE_REQUIRED');

        $this->putJson('api/v1/me/password', [
            'current_password' => self::CURRENT,
            'password' => 'Brand-new-pass-1',
            'password_confirmation' => 'Brand-new-pass-1',
        ])->assertOk();

        $this->getJson('api/v1/settings')->assertStatus(403)->assertJsonPath('code', 'FORBIDDEN');
    }
}
