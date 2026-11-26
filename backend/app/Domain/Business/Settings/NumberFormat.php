<?php

namespace App\Domain\Business\Settings;

use Carbon\CarbonInterface;

/**
 * Configurable document numbers (FR-BIZ-004), for example
 * INV-{YYYY}-{SEQ:5} gives INV-2026-00001.
 *
 * Tokens: {YYYY}, {YY}, {MM}, {DD}, {YYMMDD} and exactly one {SEQ:n}
 * (n = 1 to 8 digits, zero-padded). The sequence restarts in each period,
 * and the period is the smallest date unit in the format, so a format with
 * {DD} numbers per day and one with only {YYYY} numbers per year.
 */
final class NumberFormat
{
    public const MAX_LENGTH = 40;

    private const TOKEN = '/\{(YYYY|YY|MM|DD|YYMMDD|SEQ:(\d+))\}/';

    /**
     * Problems with the format, in plain English. Empty when valid.
     *
     * @return list<string>
     */
    public static function problems(string $format): array
    {
        $problems = [];

        if (trim($format) === '') {
            return ['Enter a format.'];
        }

        if (mb_strlen($format) > self::MAX_LENGTH) {
            $problems[] = 'Keep the format to '.self::MAX_LENGTH.' characters or fewer.';
        }

        preg_match_all('/\{SEQ:(\d+)\}/', $format, $sequences);

        if (count($sequences[0]) !== 1) {
            $problems[] = 'Include the running number exactly once, for example {SEQ:5}.';
        } elseif ((int) $sequences[1][0] < 1 || (int) $sequences[1][0] > 8) {
            $problems[] = 'The running number can have 1 to 8 digits.';
        }

        $literal = preg_replace(self::TOKEN, '', $format) ?? '';

        if (str_contains($literal, '{') || str_contains($literal, '}')) {
            $problems[] = 'Use only these codes: {YYYY}, {YY}, {MM}, {DD}, {YYMMDD} and {SEQ:n}.';
        } elseif (preg_match('/^[A-Za-z0-9\-\/_.]*$/', $literal) !== 1) {
            $problems[] = 'Use only letters, numbers and - / _ . between the codes.';
        }

        return $problems;
    }

    public static function isValid(string $format): bool
    {
        return self::problems($format) === [];
    }

    public static function render(string $format, CarbonInterface $date, int $sequence): string
    {
        return (string) preg_replace_callback(self::TOKEN, function (array $match) use ($date, $sequence): string {
            return match (true) {
                $match[1] === 'YYYY' => $date->format('Y'),
                $match[1] === 'YY' => $date->format('y'),
                $match[1] === 'MM' => $date->format('m'),
                $match[1] === 'DD' => $date->format('d'),
                $match[1] === 'YYMMDD' => $date->format('ymd'),
                default => str_pad((string) $sequence, (int) $match[2], '0', STR_PAD_LEFT),
            };
        }, $format);
    }

    /**
     * The period the running number restarts in: a day, a month, a year or never.
     */
    public static function period(string $format, CarbonInterface $date): string
    {
        return match (true) {
            str_contains($format, '{DD}') || str_contains($format, '{YYMMDD}') => $date->format('Ymd'),
            str_contains($format, '{MM}') => $date->format('Ym'),
            str_contains($format, '{YYYY}') || str_contains($format, '{YY}') => $date->format('Y'),
            default => '',
        };
    }
}
