<?php

namespace Database\Seeders\Demo\Support;

use Random\Randomizer;

/**
 * Generates fictional customer names from common Nigerian names.
 * About one in eight customers is male, reflecting typical salon clientele.
 */
final class NigerianNames
{
    /** @var array{female: list<string>, male: list<string>, surnames: list<string>, lagos_areas: list<string>} */
    private array $names;

    public function __construct(private readonly Randomizer $random)
    {
        $this->names = DemoData::names();
    }

    public function fullName(): string
    {
        return $this->firstName().' '.$this->pick($this->names['surnames']);
    }

    public function firstName(): string
    {
        $pool = $this->random->getInt(1, 8) === 1 ? $this->names['male'] : $this->names['female'];

        return $this->pick($pool);
    }

    public function lagosArea(): string
    {
        return $this->pick($this->names['lagos_areas']);
    }

    /**
     * @param  list<string>  $items
     */
    private function pick(array $items): string
    {
        return $items[$this->random->getInt(0, count($items) - 1)];
    }
}
