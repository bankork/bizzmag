<?php

namespace App\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\PreflightCheck;
use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\BusinessSetting;
use App\Domain\Business\Settings\SettingsCatalogue;
use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Spatie\Permission\Models\Permission;
use Throwable;

/**
 * Phase 1 readiness: a business is installed, the permission catalogue is
 * up to date, and sessions are stored so that disabling someone signs them
 * out everywhere and employees can stay signed in for their idle limit.
 */
final class InstallationCheck implements PreflightCheck
{
    public function __construct(private readonly CurrentBusiness $business) {}

    public function run(): array
    {
        $results = [$this->sessionStore()];

        try {
            $businesses = Business::query()->orderBy('id')->get();
            $permissions = Permission::query()->whereIn('name', PermissionCatalogue::names())->count();
        } catch (Throwable) {
            return $results;
        }

        $results[] = $businesses->isEmpty()
            ? CheckResult::warn('Business installed', 'No business has been set up yet.', 'Run: php artisan app:install')
            : CheckResult::pass('Business installed', $businesses->pluck('name')->implode(', '));

        if ($businesses->isNotEmpty()) {
            $results[] = $permissions === count(PermissionCatalogue::names())
                ? CheckResult::pass('Permissions', $permissions.' permissions')
                : CheckResult::warn('Permissions', ($permissions).' of '.count(PermissionCatalogue::names()).' permissions exist.', 'Run: php artisan app:permissions:sync');
            $results[] = $this->sessionLifetime($businesses->modelKeys());
        }

        return $results;
    }

    private function sessionStore(): CheckResult
    {
        $driver = (string) config('session.driver');

        return $driver === 'database'
            ? CheckResult::pass('Session store', 'database')
            : CheckResult::warn('Session store', "SESSION_DRIVER is \"{$driver}\". Disabling a user will not sign them out of other devices at once.", 'Set SESSION_DRIVER=database.');
    }

    /**
     * @param  array<int, int|string>  $businessIds
     */
    private function sessionLifetime(array $businessIds): CheckResult
    {
        $lifetimeMinutes = (int) config('session.lifetime');
        $longestDays = 0;

        foreach ($businessIds as $id) {
            $days = $this->business->within((int) $id, fn () => BusinessSetting::query()->where('key', 'sessions.idle_days_low_risk')->value('value'));
            $longestDays = max($longestDays, (int) ($days ?? SettingsCatalogue::default('sessions.idle_days_low_risk')));
        }

        return $lifetimeMinutes >= $longestDays * 1440
            ? CheckResult::pass('Session lifetime', "{$lifetimeMinutes} minutes covers the {$longestDays}-day employee limit")
            : CheckResult::warn('Session lifetime', "SESSION_LIFETIME is {$lifetimeMinutes} minutes, shorter than the {$longestDays}-day employee idle limit in Settings.", 'Set SESSION_LIFETIME='.($longestDays * 1440).' or lower the limit in Settings.');
    }
}
