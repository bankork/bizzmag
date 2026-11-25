<?php

namespace Tests\Feature\Console\Preflight\Checks;

use App\Console\Preflight\Checks\DatabaseCheck;
use App\Console\Preflight\CheckStatus;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseCheckTest extends TestCase
{
    public function test_the_test_database_supports_check_constraints_and_generated_unique_indexes(): void
    {
        $results = $this->app->make(DatabaseCheck::class)->run();

        $this->assertSame(
            ['Database connection', 'Database version', 'CHECK constraints', 'Generated-column unique index'],
            array_map(fn ($result) => $result->name, $results),
        );
        $this->assertSame(
            [CheckStatus::Pass, CheckStatus::Pass, CheckStatus::Pass, CheckStatus::Pass],
            array_map(fn ($result) => $result->status, $results),
        );
    }

    public function test_leaves_no_probe_table_behind(): void
    {
        $this->app->make(DatabaseCheck::class)->run();

        $this->assertFalse(Schema::hasTable('_bizzmag_preflight_probe'));
    }

    public function test_fails_with_a_fix_when_the_database_is_unreachable(): void
    {
        config(['database.connections.mysql.port' => 1]);
        $this->app['db']->purge('mysql');

        $results = $this->app->make(DatabaseCheck::class)->run();

        $this->assertCount(1, $results);
        $this->assertSame(CheckStatus::Fail, $results[0]->status);
        $this->assertStringContainsString('DB_HOST', (string) $results[0]->fix);
    }
}
