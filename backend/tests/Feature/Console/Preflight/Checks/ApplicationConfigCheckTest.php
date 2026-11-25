<?php

namespace Tests\Feature\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\Checks\ApplicationConfigCheck;
use App\Console\Preflight\CheckStatus;
use Tests\TestCase;

class ApplicationConfigCheckTest extends TestCase
{
    public function test_a_correct_production_configuration_passes(): void
    {
        $this->asProduction(['app.debug' => false, 'app.url' => 'https://api.adunni.test']);

        $statuses = array_map(fn (CheckResult $result) => $result->status, (new ApplicationConfigCheck)->run());

        $this->assertSame([CheckStatus::Pass, CheckStatus::Pass, CheckStatus::Pass, CheckStatus::Pass], $statuses);
    }

    public function test_fails_when_debug_is_on_in_production(): void
    {
        $this->asProduction(['app.debug' => true, 'app.url' => 'https://api.adunni.test']);

        $result = $this->find('Environment');

        $this->assertSame(CheckStatus::Fail, $result->status);
    }

    public function test_fails_when_the_application_key_is_missing(): void
    {
        config(['app.key' => '']);

        $this->assertSame(CheckStatus::Fail, $this->find('Application key')->status);
    }

    public function test_fails_when_timestamps_would_not_be_stored_in_utc(): void
    {
        config(['app.timezone' => 'Africa/Lagos']);

        $this->assertSame(CheckStatus::Fail, $this->find('Application timezone')->status);
    }

    public function test_fails_without_https_in_production(): void
    {
        $this->asProduction(['app.debug' => false, 'app.url' => 'http://api.adunni.test']);

        $this->assertSame(CheckStatus::Fail, $this->find('HTTPS')->status);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function asProduction(array $config): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config($config);
    }

    private function find(string $name): CheckResult
    {
        return collect((new ApplicationConfigCheck)->run())->firstOrFail(fn (CheckResult $result) => $result->name === $name);
    }
}
