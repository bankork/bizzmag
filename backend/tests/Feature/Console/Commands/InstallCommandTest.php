<?php

namespace Tests\Feature\Console\Commands;

use App\Domain\Business\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InstallCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        putenv('BIZZMAG_OWNER_PASSWORD');

        parent::tearDown();
    }

    public function test_interactive_install_creates_the_salon_roles_tax_off_and_the_owner(): void
    {
        $this->artisan('app:install')
            ->expectsQuestion('Salon name', 'Adunni Hair & Beauty Studio')
            ->expectsQuestion('Owner\'s full name', 'Adunni Bakare')
            ->expectsQuestion('Owner\'s email (leave empty to use a phone number)', 'Adunni@Adunni.test')
            ->expectsQuestion('Owner\'s password (at least 8 characters)', 'Lekki-owner-26')
            ->expectsQuestion('Type the password again', 'Lekki-owner-26')
            ->expectsOutputToContain('Installed Adunni Hair & Beauty Studio.')
            ->assertSuccessful();

        $business = Business::query()->sole();
        $owner = User::query()->sole();
        $this->assertSame('adunni@adunni.test', $owner->email);
        $this->assertTrue(Hash::check('Lekki-owner-26', $owner->password));
        $this->assertFalse($owner->must_change_password);
        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseHas('tax_settings', ['business_id' => $business->id, 'enabled' => false]);
        $this->assertDatabaseHas('audit_events', ['action' => 'business.installed', 'business_id' => $business->id]);
    }

    public function test_scripted_install_takes_the_password_from_the_environment_and_a_phone_number(): void
    {
        putenv('BIZZMAG_OWNER_PASSWORD=Lekki-owner-26');

        $this->artisan('app:install', [
            '--business' => 'Adunni Hair & Beauty Studio',
            '--owner-name' => 'Adunni Bakare',
            '--owner-phone' => '0803 000 0100',
            '--does-salon-work' => true,
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', ['phone' => '+2348030000100', 'email' => null]);
        $this->assertDatabaseCount('employee_profiles', 1);
    }

    public function test_install_refuses_mismatched_passwords(): void
    {
        $this->artisan('app:install')
            ->expectsQuestion('Salon name', 'Adunni')
            ->expectsQuestion('Owner\'s full name', 'Adunni Bakare')
            ->expectsQuestion('Owner\'s email (leave empty to use a phone number)', 'adunni@adunni.test')
            ->expectsQuestion('Owner\'s password (at least 8 characters)', 'Lekki-owner-26')
            ->expectsQuestion('Type the password again', 'Lekki-owner-27')
            ->expectsOutput('The passwords do not match. Nothing was created.')
            ->assertFailed();

        $this->assertDatabaseCount('businesses', 0);
    }

    public function test_install_refuses_a_weak_password(): void
    {
        putenv('BIZZMAG_OWNER_PASSWORD=password123');

        $this->artisan('app:install', ['--business' => 'Adunni', '--owner-name' => 'Adunni Bakare', '--owner-email' => 'adunni@adunni.test', '--no-interaction' => true])
            ->expectsOutput('This password is too easy to guess. Choose another.')
            ->assertFailed();
    }

    public function test_install_runs_only_once(): void
    {
        putenv('BIZZMAG_OWNER_PASSWORD=Lekki-owner-26');
        $options = ['--business' => 'Adunni', '--owner-name' => 'Adunni Bakare', '--no-interaction' => true];

        $this->artisan('app:install', [...$options, '--owner-email' => 'adunni@adunni.test'])->assertSuccessful();
        $this->artisan('app:install', [...$options, '--owner-email' => 'second@adunni.test'])
            ->expectsOutputToContain('This installation already has a business.')
            ->assertFailed();

        $this->assertDatabaseCount('businesses', 1);
    }
}
