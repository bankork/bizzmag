<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\BusinessSetting;
use App\Domain\Business\Settings\BusinessSettings;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\StaleVersion;
use App\Domain\Shared\Locking\RowLock;
use Illuminate\Support\Facades\DB;

/**
 * Saves the business profile and configurable values (FR-BIZ-002 to
 * FR-BIZ-006).
 *
 * The save carries the version it was based on. If someone saved in
 * between, it is refused (409 STALE) rather than silently overwriting.
 * Only changed values are audited, with before and after.
 */
final class UpdateBusinessSettings
{
    public const PROFILE_FIELDS = ['name', 'phone', 'email', 'address', 'receipt_footer', 'invoice_footer', 'timezone'];

    public function __construct(
        private readonly RowLock $locks,
        private readonly BusinessSettings $settings,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array<string, string|null>  $profile
     * @param  array<string, int|string>  $values  Keyed by SettingsCatalogue key.
     */
    public function __invoke(Business $business, int $expectedVersion, array $profile, array $values): Business
    {
        return DB::transaction(function () use ($business, $expectedVersion, $profile, $values): Business {
            $this->locks->forUpdate('businesses', $business->id);
            $business->refresh();

            if ($business->settings_version !== $expectedVersion) {
                throw new StaleVersion(context: ['current_version' => $business->settings_version]);
            }

            $before = [];
            $after = [];

            foreach (array_intersect_key($profile, array_flip(self::PROFILE_FIELDS)) as $field => $value) {
                if ($business->getAttribute($field) !== $value) {
                    $before[$field] = $business->getAttribute($field);
                    $after[$field] = $value;
                    $business->setAttribute($field, $value);
                }
            }

            $current = $this->settings->all();

            foreach ($values as $key => $value) {
                if (($current[$key] ?? null) === $value) {
                    continue;
                }

                $before[$key] = $current[$key] ?? null;
                $after[$key] = $value;

                $setting = BusinessSetting::query()->firstOrNew(['key' => $key]);
                $setting->value = $value;
                $setting->version = ($setting->exists ? $setting->version : 0) + 1;
                $setting->save();
            }

            if ($after === []) {
                return $business;
            }

            $business->settings_version++;
            $business->save();

            $this->audit->record(new AuditEntry('settings.updated', 'business', $business->id, before: $before, after: $after));

            return $business;
        });
    }
}
