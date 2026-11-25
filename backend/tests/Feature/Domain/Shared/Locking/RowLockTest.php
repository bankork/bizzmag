<?php

namespace Tests\Feature\Domain\Shared\Locking;

use App\Domain\Shared\Exceptions\NotInTransaction;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Domain\Shared\Locking\RowLock;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RowLockTest extends TestCase
{
    /** Not transaction-wrapped: these tests assert behaviour outside a transaction. */
    use DatabaseTruncation;

    public function test_returns_the_locked_row_inside_a_transaction(): void
    {
        $id = DB::table('number_sequences')->insertGetId(['sequence_key' => 'invoice', 'next_value' => 42]);

        $row = DB::transaction(fn () => $this->app->make(RowLock::class)->forUpdate('number_sequences', $id));

        $this->assertSame(42, (int) $row->next_value);
    }

    public function test_refuses_to_lock_outside_a_transaction(): void
    {
        $this->expectException(NotInTransaction::class);

        $this->app->make(RowLock::class)->forUpdate('number_sequences', 1);
    }

    public function test_reports_a_missing_row(): void
    {
        $this->expectException(RecordNotFound::class);

        DB::transaction(fn () => $this->app->make(RowLock::class)->forUpdate('number_sequences', 999999));
    }
}
