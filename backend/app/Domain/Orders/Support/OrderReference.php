<?php

namespace App\Domain\Orders\Support;

use App\Domain\Business\Settings\BusinessSettings;
use App\Domain\Business\Settings\NumberFormat;
use App\Domain\Shared\Sequences\Sequence;
use Carbon\CarbonInterface;

/**
 * The visit reference (FR-CUS-004, FR-ORD-008), for example V-261126-007,
 * from the owner's numbering format and a gap-free sequence that restarts in
 * each period of the format. Guest and customer visits share the scheme.
 */
final class OrderReference
{
    public const SEQUENCE_KEY = 'order';

    public function __construct(
        private readonly Sequence $sequence,
        private readonly BusinessSettings $settings,
    ) {}

    /**
     * @param  CarbonInterface  $localNow  The current time in the business's timezone.
     */
    public function next(int $businessId, CarbonInterface $localNow): string
    {
        $format = (string) $this->settings->get('numbering.order');
        $number = $this->sequence->next(self::SEQUENCE_KEY, NumberFormat::period($format, $localNow), $businessId);

        return NumberFormat::render($format, $localNow, $number);
    }
}
