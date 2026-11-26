<?php

namespace Tests\Unit\Domain\Business\Settings;

use App\Domain\Business\Settings\NumberFormat;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NumberFormatTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: int, 2: string, 3: string}>
     */
    public static function formats(): array
    {
        return [
            'default visit reference' => ['V-{YYMMDD}-{SEQ:3}', 7, 'V-261125-007', '20261125'],
            'default invoice' => ['INV-{YYYY}-{SEQ:5}', 77, 'INV-2026-00077', '2026'],
            'monthly receipt' => ['RCT/{YY}{MM}/{SEQ:4}', 12, 'RCT/2611/0012', '202611'],
            'daily with separate parts' => ['{DD}.{MM}.{YYYY}-{SEQ:2}', 3, '25.11.2026-03', '20261125'],
            'never restarts' => ['ADU-{SEQ:6}', 123456, 'ADU-123456', ''],
            'number wider than its padding' => ['N{SEQ:2}', 1234, 'N1234', ''],
        ];
    }

    #[DataProvider('formats')]
    public function test_formats_render_and_restart_in_the_right_period(string $format, int $sequence, string $expected, string $period): void
    {
        $date = CarbonImmutable::parse('2026-11-25 14:00', 'Africa/Lagos');

        $this->assertSame([], NumberFormat::problems($format));
        $this->assertSame($expected, NumberFormat::render($format, $date, $sequence));
        $this->assertSame($period, NumberFormat::period($format, $date));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function invalidFormats(): array
    {
        return [
            'no running number' => ['INV-{YYYY}', 'Include the running number exactly once, for example {SEQ:5}.'],
            'two running numbers' => ['{SEQ:2}-{SEQ:3}', 'Include the running number exactly once, for example {SEQ:5}.'],
            'running number too wide' => ['INV-{SEQ:9}', 'The running number can have 1 to 8 digits.'],
            'unknown code' => ['INV-{WEEK}-{SEQ:3}', 'Use only these codes: {YYYY}, {YY}, {MM}, {DD}, {YYMMDD} and {SEQ:n}.'],
            'space' => ['INV {SEQ:3}', 'Use only letters, numbers and - / _ . between the codes.'],
            'too long' => [str_repeat('A', 41).'{SEQ:3}', 'Keep the format to 40 characters or fewer.'],
            'empty' => ['', 'Enter a format.'],
        ];
    }

    #[DataProvider('invalidFormats')]
    public function test_invalid_formats_explain_the_problem(string $format, string $problem): void
    {
        $this->assertContains($problem, NumberFormat::problems($format));
    }
}
