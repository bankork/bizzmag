<?php

namespace App\Domain\Business\Settings;

use App\Domain\Business\Models\BusinessSetting;

/**
 * Reads the current business's settings, falling back to the catalogue
 * defaults for anything never saved.
 */
final class BusinessSettings
{
    /**
     * @return array<string, int|string>
     */
    public function all(): array
    {
        $saved = BusinessSetting::query()->pluck('value', 'key')->all();
        $values = SettingsCatalogue::defaults();

        foreach ($saved as $key => $value) {
            if (SettingsCatalogue::has($key)) {
                $values[$key] = $value;
            }
        }

        return $values;
    }

    public function get(string $key): int|string
    {
        $value = BusinessSetting::query()->where('key', $key)->value('value');

        return $value ?? SettingsCatalogue::default($key);
    }

    public function integer(string $key): int
    {
        return (int) $this->get($key);
    }
}
