<?php

namespace App\Console\Preflight;

/**
 * One host-readiness check run by `php artisan app:preflight`.
 */
interface PreflightCheck
{
    /**
     * @return list<CheckResult>
     */
    public function run(): array;
}
