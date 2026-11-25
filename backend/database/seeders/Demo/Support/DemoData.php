<?php

namespace Database\Seeders\Demo\Support;

/**
 * Loads the version-controlled demo dataset definitions from Demo/Data.
 */
final class DemoData
{
    /**
     * @return array<string, mixed>
     */
    public static function salon(): array
    {
        return require self::path('salon');
    }

    /**
     * @return list<array{key: string, name: string, email: string, role: string, skills: list<string>, apprentice?: bool}>
     */
    public static function staff(): array
    {
        return require self::path('staff');
    }

    /**
     * @return array{services: array<string, list<array<string, mixed>>>, products: array<string, list<array<string, mixed>>>}
     */
    public static function catalogue(): array
    {
        return require self::path('catalogue');
    }

    /**
     * @return array{female: list<string>, male: list<string>, surnames: list<string>, lagos_areas: list<string>}
     */
    public static function names(): array
    {
        return require self::path('names');
    }

    /**
     * @return array{transfer_sources: list<array{bank: string, format: string}>, pos_terminals: list<array{provider: string, format: string}>}
     */
    public static function payments(): array
    {
        return require self::path('payments');
    }

    /**
     * Find one catalogue service or variant price by name, as naira.
     * For example price('Knotless braids', 'Medium') returns "40,000".
     */
    public static function price(string $service, ?string $variant = null): string
    {
        $catalogue = self::catalogue();

        foreach ([...$catalogue['services'], ...$catalogue['products']] as $items) {
            foreach ($items as $item) {
                if ($item['name'] !== $service) {
                    continue;
                }

                return $variant === null ? $item['price'] : $item['variants'][$variant];
            }
        }

        throw new \InvalidArgumentException("Unknown demo catalogue item: {$service}.");
    }

    private static function path(string $file): string
    {
        return dirname(__DIR__).'/Data/'.$file.'.php';
    }
}
