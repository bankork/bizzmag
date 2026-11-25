<?php

namespace Database\Seeders\Demo\Support;

use Random\Randomizer;

/**
 * Generates fictional Nigerian mobile numbers in E.164 form (+234...).
 *
 * Numbers use real network prefixes so they look and validate like real
 * numbers, with a fixed "000" block to keep them recognisable as demo data.
 * Nigeria has no reserved fictional range, so a generated number could in
 * principle belong to someone: demo data must never be used to send SMS or
 * WhatsApp messages.
 */
final class NigerianPhoneNumbers
{
    /**
     * MTN, Glo, Airtel and 9mobile prefixes.
     *
     * @var list<string>
     */
    public const PREFIXES = [
        '0803', '0806', '0703', '0706', '0813', '0816', '0810', '0814', '0903', '0906',
        '0805', '0807', '0705', '0815', '0811', '0905',
        '0802', '0808', '0708', '0812', '0701', '0902', '0901', '0907',
        '0809', '0817', '0818', '0909', '0908',
    ];

    /** @var array<string, true> */
    private array $issued = [];

    public function __construct(private readonly Randomizer $random) {}

    /**
     * A new number not issued before by this generator, for example "+2348030004821".
     */
    public function next(): string
    {
        do {
            $prefix = self::PREFIXES[$this->random->getInt(0, count(self::PREFIXES) - 1)];
            $number = '+234'.substr($prefix, 1).'000'.str_pad((string) $this->random->getInt(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (isset($this->issued[$number]));

        $this->issued[$number] = true;

        return $number;
    }

    /**
     * Local display form, for example "0803 000 4821".
     */
    public static function toLocal(string $e164): string
    {
        $local = '0'.substr($e164, 4);

        return substr($local, 0, 4).' '.substr($local, 4, 3).' '.substr($local, 7);
    }
}
