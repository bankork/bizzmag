<?php

namespace App\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\PreflightCheck;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Migrations are up to date and the tables used by the queue and cache exist.
 */
final class SchemaCheck implements PreflightCheck
{
    /** @var list<string> */
    private const REQUIRED_TABLES = ['jobs', 'failed_jobs', 'job_batches', 'cache', 'sessions'];

    public function __construct(private readonly Migrator $migrator) {}

    public function run(): array
    {
        try {
            if (! $this->migrator->repositoryExists()) {
                return [CheckResult::fail('Migrations', 'The database has not been migrated.', 'Run: php artisan migrate --force')];
            }

            $files = array_keys($this->migrator->getMigrationFiles([database_path('migrations')]));
            $pending = array_values(array_diff($files, $this->migrator->getRepository()->getRan()));
            $missingTables = array_values(array_filter(self::REQUIRED_TABLES, fn (string $table) => ! Schema::hasTable($table)));
        } catch (Throwable) {
            return [CheckResult::fail('Migrations', 'The migration status could not be read.', 'Fix the database connection first.')];
        }

        return [
            $pending === []
                ? CheckResult::pass('Migrations', count($files).' migrations applied')
                : CheckResult::fail('Migrations', count($pending).' pending: '.implode(', ', array_slice($pending, 0, 3)).(count($pending) > 3 ? ', ...' : ''), 'Run: php artisan migrate --force'),
            $missingTables === []
                ? CheckResult::pass('Queue, cache and session tables', 'Present')
                : CheckResult::fail('Queue, cache and session tables', 'Missing: '.implode(', ', $missingTables), 'Run: php artisan migrate --force'),
        ];
    }
}
