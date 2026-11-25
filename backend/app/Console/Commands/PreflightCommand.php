<?php

namespace App\Console\Commands;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\Checks\ApplicationConfigCheck;
use App\Console\Preflight\Checks\DatabaseCheck;
use App\Console\Preflight\Checks\DomainConsistencyCheck;
use App\Console\Preflight\Checks\FilesystemCheck;
use App\Console\Preflight\Checks\PhpRuntimeCheck;
use App\Console\Preflight\Checks\SchedulerCheck;
use App\Console\Preflight\Checks\SchemaCheck;
use App\Console\Preflight\CheckStatus;
use App\Console\Preflight\PreflightCheck;
use Illuminate\Console\Command;

/**
 * Checks whether this server can run the application correctly.
 *
 * Run on the host after every deployment. Exits with code 1 if any check
 * fails, so deploy scripts and CI can stop on a broken host.
 */
class PreflightCommand extends Command
{
    protected $signature = 'app:preflight
                            {--json : Print the results as JSON (for scripts and CI)}';

    protected $description = 'Check that this server meets every requirement to run the application';

    /**
     * @var list<class-string<PreflightCheck>>
     */
    private const CHECKS = [
        PhpRuntimeCheck::class,
        DatabaseCheck::class,
        SchemaCheck::class,
        FilesystemCheck::class,
        ApplicationConfigCheck::class,
        DomainConsistencyCheck::class,
        SchedulerCheck::class,
    ];

    public function handle(): int
    {
        $results = [];

        foreach (self::CHECKS as $check) {
            array_push($results, ...$this->laravel->make($check)->run());
        }

        $failed = array_filter($results, fn (CheckResult $result) => $result->status === CheckStatus::Fail);
        $warned = array_filter($results, fn (CheckResult $result) => $result->status === CheckStatus::Warn);

        if ($this->option('json')) {
            $this->line(json_encode([
                'passed' => $failed === [],
                'results' => array_map(fn (CheckResult $result) => [
                    'name' => $result->name,
                    'status' => $result->status->value,
                    'detail' => $result->detail,
                    'fix' => $result->fix,
                ], $results),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $failed === [] ? self::SUCCESS : self::FAILURE;
        }

        $this->table(['Result', 'Check', 'Detail'], array_map(fn (CheckResult $result) => [
            match ($result->status) {
                CheckStatus::Pass => '<info>PASS</info>',
                CheckStatus::Warn => '<comment>WARN</comment>',
                CheckStatus::Fail => '<error>FAIL</error>',
            },
            $result->name,
            $result->detail,
        ], $results));

        foreach ([...$failed, ...$warned] as $result) {
            if ($result->fix !== null) {
                $this->line("  <options=bold>{$result->name}:</> {$result->fix}");
            }
        }

        $this->newLine();

        if ($failed !== []) {
            $this->error(count($failed).' check(s) failed. Fix them before using this server.');

            return self::FAILURE;
        }

        $this->info('All required checks passed'.($warned !== [] ? ' ('.count($warned).' warning(s)).' : '.'));

        return self::SUCCESS;
    }
}
