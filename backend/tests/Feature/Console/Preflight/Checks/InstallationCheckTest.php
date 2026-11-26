<?php

namespace Tests\Feature\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\Checks\InstallationCheck;
use App\Console\Preflight\CheckStatus;
use App\Domain\Business\Models\BusinessSetting;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Spatie\Permission\Models\Permission;
use Tests\Feature\Api\V1\ApiTestCase;

class InstallationCheckTest extends ApiTestCase
{
    public function test_a_fresh_database_is_told_to_run_the_installer(): void
    {
        config(['session.driver' => 'database']);

        $results = $this->results();

        $this->assertSame(CheckStatus::Warn, $results['Business installed']->status);
        $this->assertSame('Run: php artisan app:install', $results['Business installed']->fix);
        $this->assertSame(CheckStatus::Pass, $results['Session store']->status);
    }

    public function test_an_installed_business_with_matching_settings_passes(): void
    {
        config(['session.driver' => 'database', 'session.lifetime' => 10080]);
        $this->installSalon();

        $results = $this->results();

        $this->assertSame(CheckStatus::Pass, $results['Business installed']->status);
        $this->assertSame(CheckStatus::Pass, $results['Permissions']->status);
        $this->assertSame(CheckStatus::Pass, $results['Session lifetime']->status);
    }

    public function test_missing_permissions_and_a_short_session_lifetime_are_flagged(): void
    {
        config(['session.driver' => 'file', 'session.lifetime' => 120]);
        $salon = $this->installSalon();
        Permission::query()->where('name', 'import.run')->delete();
        app(CurrentBusiness::class)->within($salon['business']->id, fn () => BusinessSetting::query()->create(['key' => 'sessions.idle_days_low_risk', 'value' => 14]));

        $results = $this->results();

        $this->assertSame(CheckStatus::Warn, $results['Session store']->status);
        $this->assertSame('Run: php artisan app:permissions:sync', $results['Permissions']->fix);
        $this->assertSame('Set SESSION_LIFETIME=20160 or lower the limit in Settings.', $results['Session lifetime']->fix);
    }

    /**
     * @return array<string, CheckResult>
     */
    private function results(): array
    {
        $byName = [];

        foreach (app(InstallationCheck::class)->run() as $result) {
            $byName[$result->name] = $result;
        }

        return $byName;
    }
}
