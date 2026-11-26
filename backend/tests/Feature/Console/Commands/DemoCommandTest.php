<?php

namespace Tests\Feature\Console\Commands;

use App\Domain\Business\Models\Business;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DemoCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeds_the_demo_staff_accounts(): void
    {
        $this->artisan('app:demo')
            ->expectsOutputToContain('Demo salon loaded: Adunni Hair & Beauty Studio')
            ->assertSuccessful();

        $this->assertDatabaseCount('users', 12);
        $this->assertDatabaseHas('users', ['email' => 'chiamaka@adunni.test', 'name' => 'Chiamaka Eze']);
    }

    public function test_every_demo_staff_member_has_their_role_a_phone_and_a_salon(): void
    {
        $this->artisan('app:demo')->assertSuccessful();

        $business = Business::query()->sole();
        $roles = app(CurrentBusiness::class)->within($business->id, fn () => User::query()
            ->with('roles')->orderBy('id')->get()
            ->mapWithKeys(fn (User $user) => [$user->email => $user->roles->pluck('system_key')->all()])
            ->all());

        $this->assertSame(['owner'], $roles['adunni@adunni.test']);
        $this->assertSame(['supervisor'], $roles['ngozi@adunni.test']);
        $this->assertSame(['cashier'], $roles['kemi@adunni.test']);
        $this->assertSame(['employee'], $roles['precious@adunni.test']);
        $this->assertSame(0, User::query()->whereNull('phone')->count());
        $this->assertDatabaseCount('business_user', 12);
        $this->assertDatabaseCount('employee_profiles', 10);
        $this->assertDatabaseHas('tax_settings', ['business_id' => $business->id, 'enabled' => false]);
        $this->assertDatabaseHas('employee_profiles', ['display_name' => 'Precious', 'job_title' => 'Apprentice']);
    }

    public function test_running_twice_does_not_duplicate_anything(): void
    {
        $this->artisan('app:demo')->assertSuccessful();
        $phones = User::query()->orderBy('id')->pluck('phone')->all();
        $this->artisan('app:demo')->assertSuccessful();

        $this->assertDatabaseCount('users', 12);
        $this->assertDatabaseCount('businesses', 1);
        $this->assertDatabaseCount('roles', 4);
        $this->assertSame($phones, User::query()->orderBy('id')->pluck('phone')->all());
    }

    public function test_demo_staff_can_sign_in_with_email_or_phone(): void
    {
        $this->artisan('app:demo')->assertSuccessful();
        $kemi = User::query()->where('email', 'kemi@adunni.test')->sole();
        $headers = ['Origin' => 'http://localhost:3000'];

        $this->postJson('api/v1/auth/login', ['identifier' => 'kemi@adunni.test', 'password' => 'Adunni@2026'], $headers)->assertOk();
        $this->postJson('api/v1/auth/logout', [], $headers)->assertOk();
        $this->postJson('api/v1/auth/login', ['identifier' => (string) $kemi->phone, 'password' => 'Adunni@2026'], $headers)
            ->assertOk()
            ->assertJsonPath('data.home', 'desk');
    }

    public function test_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('app:demo')
            ->expectsOutput('Demo data cannot be loaded in production.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_fresh_rebuild_stops_when_not_confirmed(): void
    {
        $this->artisan('app:demo', ['--fresh' => true])
            ->expectsConfirmation('This deletes ALL data in the "bizzmag_testing" database. Continue?', 'no')
            ->expectsOutput('Nothing changed.')
            ->assertFailed();
    }
}
