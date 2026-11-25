<?php

namespace App\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\PreflightCheck;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Throwable;

/**
 * The database engine, its version, and the two features the data-integrity
 * design depends on: enforced CHECK constraints, and a unique index on a
 * stored generated column (one active execution per service, SRS §7.7.3).
 *
 * The feature test uses a temporary table, so nothing is left behind.
 */
final class DatabaseCheck implements PreflightCheck
{
    private const PROBE_TABLE = '_bizzmag_preflight_probe';

    public function __construct(private readonly ConnectionInterface $db) {}

    public function run(): array
    {
        try {
            $version = (string) $this->db->selectOne('select version() as version')->version;
        } catch (Throwable $exception) {
            return [CheckResult::fail('Database connection', 'Cannot connect: '.$this->safeMessage($exception), 'Check DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME and DB_PASSWORD in .env.')];
        }

        $results = [
            CheckResult::pass('Database connection', "Connected to {$this->db->getDatabaseName()}"),
            $this->versionResult($version),
        ];

        return [...$results, ...$this->featureResults()];
    }

    private function versionResult(string $version): CheckResult
    {
        $isMariaDb = str_contains(strtolower($version), 'mariadb');
        preg_match('/^(\d+\.\d+\.\d+)/', $version, $matches);
        $numeric = $matches[1] ?? '0.0.0';
        $engine = $isMariaDb ? 'MariaDB' : 'MySQL';
        $minimum = config($isMariaDb ? 'bizzmag.minimums.mariadb' : 'bizzmag.minimums.mysql');

        return version_compare($numeric, $minimum, '>=')
            ? CheckResult::pass('Database version', "{$engine} {$numeric}")
            : CheckResult::fail('Database version', "{$engine} {$numeric} is older than {$minimum}.", 'Use MySQL 8.0.16+ or MariaDB 10.6+. Ask your host to upgrade, or use a VPS.');
    }

    /**
     * @return list<CheckResult>
     */
    private function featureResults(): array
    {
        try {
            $this->db->statement('drop temporary table if exists '.self::PROBE_TABLE);
            $this->db->statement('create temporary table '.self::PROBE_TABLE.' (
                id int unsigned auto_increment primary key,
                item_id int unsigned not null,
                status varchar(16) not null,
                amount_kobo bigint not null,
                active_item_key int unsigned as (if(status in (\'confirmed\', \'disputed\'), item_id, null)) stored,
                constraint '.self::PROBE_TABLE.'_amount check (amount_kobo >= 0),
                unique key '.self::PROBE_TABLE.'_active (active_item_key)
            )');
        } catch (Throwable $exception) {
            return [CheckResult::fail('Database features', 'Cannot create a test table with a generated column and CHECK constraint: '.$this->safeMessage($exception), 'The database user needs CREATE TEMPORARY TABLES, and the engine must be MySQL 8.0.16+ or MariaDB 10.6+.')];
        }

        try {
            return [$this->checkConstraintResult(), $this->generatedUniqueResult()];
        } finally {
            $this->db->statement('drop temporary table if exists '.self::PROBE_TABLE);
        }
    }

    private function checkConstraintResult(): CheckResult
    {
        try {
            $this->db->insert('insert into '.self::PROBE_TABLE.' (item_id, status, amount_kobo) values (1, ?, -1)', ['cancelled']);
        } catch (QueryException) {
            return CheckResult::pass('CHECK constraints', 'Enforced (negative money is rejected by the database)');
        }

        return CheckResult::fail('CHECK constraints', 'The database accepted a negative amount. CHECK constraints are not enforced.', 'Use MySQL 8.0.16+ or MariaDB 10.6+.');
    }

    private function generatedUniqueResult(): CheckResult
    {
        $insert = 'insert into '.self::PROBE_TABLE.' (item_id, status, amount_kobo) values (?, ?, 0)';

        try {
            $this->db->insert($insert, [7, 'cancelled']);
            $this->db->insert($insert, [7, 'cancelled']);
            $this->db->insert($insert, [7, 'confirmed']);
        } catch (Throwable $exception) {
            return CheckResult::fail('Generated-column unique index', 'Inactive rows were wrongly rejected: '.$this->safeMessage($exception), 'Use MySQL 8.0.16+ or MariaDB 10.6+.');
        }

        try {
            $this->db->insert($insert, [7, 'disputed']);
        } catch (UniqueConstraintViolationException) {
            return CheckResult::pass('Generated-column unique index', 'Enforced (one active execution per service)');
        }

        return CheckResult::fail('Generated-column unique index', 'A second active row was accepted.', 'Use MySQL 8.0.16+ or MariaDB 10.6+.');
    }

    private function safeMessage(Throwable $exception): string
    {
        return preg_replace('/\(Connection: .*$/s', '', $exception->getMessage()) ?? 'unknown error';
    }
}
