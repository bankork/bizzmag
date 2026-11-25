<?php

namespace Tests\Feature\Console\Commands;

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

    public function test_running_twice_does_not_duplicate_staff(): void
    {
        $this->artisan('app:demo')->assertSuccessful();
        $this->artisan('app:demo')->assertSuccessful();

        $this->assertDatabaseCount('users', 12);
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
