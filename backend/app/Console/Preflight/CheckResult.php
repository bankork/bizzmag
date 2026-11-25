<?php

namespace App\Console\Preflight;

final readonly class CheckResult
{
    private function __construct(
        public string $name,
        public CheckStatus $status,
        public string $detail,
        public ?string $fix = null,
    ) {}

    public static function pass(string $name, string $detail): self
    {
        return new self($name, CheckStatus::Pass, $detail);
    }

    public static function warn(string $name, string $detail, ?string $fix = null): self
    {
        return new self($name, CheckStatus::Warn, $detail, $fix);
    }

    public static function fail(string $name, string $detail, ?string $fix = null): self
    {
        return new self($name, CheckStatus::Fail, $detail, $fix);
    }
}
