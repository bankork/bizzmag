<?php

namespace App\Http\Presenters;

use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\TaxSetting;
use App\Domain\Business\Settings\NumberFormat;
use App\Domain\Business\Settings\SettingsCatalogue;

/**
 * API views of the business and its settings.
 */
final class BusinessPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function summary(Business $business): array
    {
        return [
            'id' => $business->id,
            'name' => $business->name,
            'currency' => $business->currency,
            'timezone' => $business->timezone,
            'logo_url' => self::logoUrl($business),
        ];
    }

    /**
     * @param  array<string, int|string>  $values
     * @return array<string, mixed>
     */
    public static function settings(Business $business, array $values): array
    {
        $today = now($business->timezone);
        $setting = fn (string $key) => [
            'value' => $values[$key],
            'default' => SettingsCatalogue::default($key),
            'range' => SettingsCatalogue::range($key),
        ];

        $numbering = [];

        foreach (['order', 'invoice', 'receipt'] as $document) {
            $format = (string) $values["numbering.{$document}"];
            $numbering[$document] = [
                'format' => $format,
                'default' => SettingsCatalogue::default("numbering.{$document}"),
                'example' => NumberFormat::render($format, $today, 1),
            ];
        }

        return [
            'version' => $business->settings_version,
            'profile' => [
                'name' => $business->name,
                'phone' => $business->phone,
                'email' => $business->email,
                'address' => $business->address,
                'receipt_footer' => $business->receipt_footer,
                'invoice_footer' => $business->invoice_footer,
                'timezone' => $business->timezone,
                'currency' => $business->currency,
                'logo_url' => self::logoUrl($business),
            ],
            'numbering' => $numbering,
            'windows' => [
                'late_submission_hours' => $setting('windows.late_submission_hours'),
                'transfer_alert_days' => $setting('windows.transfer_alert_days'),
                'share_link_days' => $setting('windows.share_link_days'),
            ],
            'sessions' => [
                'idle_minutes_privileged' => $setting('sessions.idle_minutes_privileged'),
                'idle_days_low_risk' => $setting('sessions.idle_days_low_risk'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function tax(TaxSetting $tax): array
    {
        return [
            'version' => $tax->version,
            'enabled' => $tax->enabled,
            'name' => $tax->name,
            'rate_bp' => $tax->rate_bp,
            'mode' => $tax->mode->value,
            'updated_at' => $tax->updated_at?->toIso8601String(),
        ];
    }

    private static function logoUrl(Business $business): ?string
    {
        if ($business->logo_path === null) {
            return null;
        }

        return route('api.v1.business.logo', [
            'business' => $business->id,
            'v' => substr(hash('sha256', $business->logo_path), 0, 12),
        ]);
    }
}
