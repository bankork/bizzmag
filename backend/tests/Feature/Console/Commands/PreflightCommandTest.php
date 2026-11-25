<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class PreflightCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_exits_successfully_when_only_warnings_are_found(): void
    {
        $this->artisan('app:preflight')
            ->expectsOutputToContain('All required checks passed')
            ->assertSuccessful();
    }

    public function test_exits_with_failure_when_a_check_fails(): void
    {
        config(['app.timezone' => 'Africa/Lagos']);

        $this->artisan('app:preflight')
            ->expectsOutputToContain('check(s) failed')
            ->assertFailed();
    }

    public function test_json_output_lists_every_result_for_scripts(): void
    {
        $exitCode = Artisan::call('app:preflight', ['--json' => true]);
        $report = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame(0, $exitCode);
        $this->assertTrue($report['passed']);
        $this->assertContains('Generated-column unique index', array_column($report['results'], 'name'));
        $this->assertSame(['name', 'status', 'detail', 'fix'], array_keys($report['results'][0]));
    }
}
