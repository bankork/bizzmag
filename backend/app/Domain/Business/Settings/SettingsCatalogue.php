<?php

namespace App\Domain\Business\Settings;

/**
 * Every configurable value with its default and allowed range
 * (FR-BIZ-004, FR-BIZ-005, SEC-018). The settings screen groups them.
 */
final class SettingsCatalogue
{
    /**
     * @var array<string, array{default: int|string, type: 'format'|'integer', min?: int, max?: int, financial: bool}>
     */
    private const SETTINGS = [
        'numbering.order' => ['default' => 'V-{YYMMDD}-{SEQ:3}', 'type' => 'format', 'financial' => true],
        'numbering.invoice' => ['default' => 'INV-{YYYY}-{SEQ:5}', 'type' => 'format', 'financial' => true],
        'numbering.receipt' => ['default' => 'RCT-{YYYY}-{SEQ:5}', 'type' => 'format', 'financial' => true],
        'windows.late_submission_hours' => ['default' => 48, 'type' => 'integer', 'min' => 1, 'max' => 168, 'financial' => true],
        'windows.transfer_alert_days' => ['default' => 2, 'type' => 'integer', 'min' => 1, 'max' => 14, 'financial' => true],
        'windows.share_link_days' => ['default' => 7, 'type' => 'integer', 'min' => 1, 'max' => 30, 'financial' => true],
        'sessions.idle_minutes_privileged' => ['default' => 30, 'type' => 'integer', 'min' => 5, 'max' => 120, 'financial' => false],
        'sessions.idle_days_low_risk' => ['default' => 7, 'type' => 'integer', 'min' => 1, 'max' => 30, 'financial' => false],
    ];

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::SETTINGS);
    }

    public static function has(string $key): bool
    {
        return isset(self::SETTINGS[$key]);
    }

    public static function default(string $key): int|string
    {
        return self::SETTINGS[$key]['default'];
    }

    /**
     * @return array<string, int|string>
     */
    public static function defaults(): array
    {
        return array_map(fn (array $setting) => $setting['default'], self::SETTINGS);
    }

    /**
     * Laravel validation rules for one setting.
     *
     * @return list<mixed>
     */
    public static function rules(string $key): array
    {
        $setting = self::SETTINGS[$key];

        if ($setting['type'] === 'format') {
            return ['string', 'max:'.NumberFormat::MAX_LENGTH, function (string $attribute, mixed $value, \Closure $fail): void {
                foreach (NumberFormat::problems((string) $value) as $problem) {
                    $fail($problem);
                }
            }];
        }

        return ['integer', 'min:'.$setting['min'], 'max:'.$setting['max']];
    }

    /**
     * @return array{min: int, max: int}|null
     */
    public static function range(string $key): ?array
    {
        $setting = self::SETTINGS[$key];

        return isset($setting['min'], $setting['max']) ? ['min' => $setting['min'], 'max' => $setting['max']] : null;
    }
}
