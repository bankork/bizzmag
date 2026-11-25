<?php

namespace Tests\Feature\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\Checks\DomainConsistencyCheck;
use App\Console\Preflight\CheckStatus;
use Tests\TestCase;

class DomainConsistencyCheckTest extends TestCase
{
    public function test_subdomains_of_one_domain_pass_in_production(): void
    {
        $this->productionDomains();

        $statuses = array_map(fn (CheckResult $result) => $result->status, (new DomainConsistencyCheck)->run());

        $this->assertSame(array_fill(0, 4, CheckStatus::Pass), $statuses);
    }

    public function test_a_vercel_app_frontend_fails_because_login_cookies_cannot_be_shared(): void
    {
        $this->productionDomains(['bizzmag.frontend_url' => 'https://bizzmag.vercel.app']);

        $result = $this->find('Vercel domain');

        $this->assertSame(CheckStatus::Fail, $result->status);
        $this->assertStringContainsString('custom domain', (string) $result->fix);
    }

    public function test_fails_in_production_when_the_session_domain_is_missing(): void
    {
        $this->productionDomains(['session.domain' => null]);

        $this->assertSame(CheckStatus::Fail, $this->find('Session cookie domain')->status);
    }

    public function test_fails_when_the_session_domain_does_not_cover_the_api(): void
    {
        $this->productionDomains(['session.domain' => '.salon-app.test', 'app.url' => 'https://api.adunni.test']);

        $this->assertSame(CheckStatus::Fail, $this->find('Session cookie domain')->status);
    }

    public function test_fails_when_the_frontend_is_not_an_allowed_cors_origin(): void
    {
        $this->productionDomains(['cors.allowed_origins' => ['https://other.adunni.test']]);

        $this->assertSame(CheckStatus::Fail, $this->find('CORS origin')->status);
    }

    public function test_the_same_problem_is_only_a_warning_outside_production(): void
    {
        config([
            'bizzmag.frontend_url' => 'http://localhost:3000',
            'app.url' => 'http://bizzmag-api.test',
            'session.domain' => null,
        ]);

        $this->assertSame(CheckStatus::Warn, $this->find('Session cookie domain')->status);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function productionDomains(array $overrides = []): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        config([
            'bizzmag.frontend_url' => 'https://app.adunni.test',
            'app.url' => 'https://api.adunni.test',
            'cors.allowed_origins' => ['https://app.adunni.test'],
            'sanctum.stateful' => ['app.adunni.test'],
            'session.domain' => '.adunni.test',
            'session.secure' => true,
            'session.same_site' => 'lax',
            ...$overrides,
        ]);
    }

    private function find(string $name): CheckResult
    {
        return collect((new DomainConsistencyCheck)->run())->firstOrFail(fn (CheckResult $result) => $result->name === $name);
    }
}
