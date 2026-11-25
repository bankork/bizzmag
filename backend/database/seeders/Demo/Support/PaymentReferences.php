<?php

namespace Database\Seeders\Demo\Support;

use Random\Randomizer;

/**
 * Generates transfer narrations and POS reference numbers in the styles
 * seen on Nigerian alerts and terminal slips. Every value is invented.
 */
final class PaymentReferences
{
    /** @var array{transfer_sources: list<array{bank: string, format: string}>, pos_terminals: list<array{provider: string, format: string}>} */
    private array $styles;

    public function __construct(private readonly Randomizer $random)
    {
        $this->styles = DemoData::payments();
    }

    /**
     * @return array{bank: string, reference: string}
     */
    public function transfer(string $senderName): array
    {
        $source = $this->styles['transfer_sources'][$this->random->getInt(0, count($this->styles['transfer_sources']) - 1)];

        return [
            'bank' => $source['bank'],
            'reference' => str_replace('{NAME}', mb_strtoupper($senderName), $source['format']),
        ];
    }

    /**
     * @return array{provider: string, reference: string}
     */
    public function pos(): array
    {
        $terminal = $this->styles['pos_terminals'][$this->random->getInt(0, count($this->styles['pos_terminals']) - 1)];
        $digits = '';

        for ($i = 0; $i < 12; $i++) {
            $digits .= (string) $this->random->getInt(0, 9);
        }

        return [
            'provider' => $terminal['provider'],
            'reference' => str_replace('{DIGITS12}', $digits, $terminal['format']),
        ];
    }
}
