<?php

namespace App\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\PreflightCheck;

/**
 * Application key, environment safety, timezone and HTTPS.
 */
final class ApplicationConfigCheck implements PreflightCheck
{
    public function run(): array
    {
        $production = app()->isProduction();

        return [
            $this->appKey(),
            $this->environment($production),
            $this->timezone(),
            $this->https($production),
        ];
    }

    private function appKey(): CheckResult
    {
        return filled(config('app.key'))
            ? CheckResult::pass('Application key', 'APP_KEY is set')
            : CheckResult::fail('Application key', 'APP_KEY is empty.', 'Run: php artisan key:generate --force');
    }

    private function environment(bool $production): CheckResult
    {
        $environment = app()->environment();

        if ($production && config('app.debug')) {
            return CheckResult::fail('Environment', 'APP_DEBUG is true in production. Error pages would expose internal details.', 'Set APP_DEBUG=false in .env, then run php artisan config:cache.');
        }

        if (! $production) {
            return CheckResult::warn('Environment', "APP_ENV is \"{$environment}\". Use \"production\" on the live server.", 'Set APP_ENV=production and APP_DEBUG=false on the live server.');
        }

        return CheckResult::pass('Environment', 'production, debug off');
    }

    private function timezone(): CheckResult
    {
        return config('app.timezone') === 'UTC'
            ? CheckResult::pass('Application timezone', 'UTC (the business timezone is a separate setting)')
            : CheckResult::fail('Application timezone', 'APP_TIMEZONE is '.config('app.timezone').'. Timestamps must be stored in UTC.', 'Set APP_TIMEZONE=UTC. The salon timezone (Africa/Lagos) is configured in business settings.');
    }

    private function https(bool $production): CheckResult
    {
        $secure = str_starts_with((string) config('app.url'), 'https://');

        if ($secure) {
            return CheckResult::pass('HTTPS', 'APP_URL uses https');
        }

        return $production
            ? CheckResult::fail('HTTPS', 'APP_URL does not use https.', 'Enable SSL (cPanel: AutoSSL, VPS: certbot) and set APP_URL=https://api.your-domain.')
            : CheckResult::warn('HTTPS', 'APP_URL does not use https (acceptable on a development machine).');
    }
}
