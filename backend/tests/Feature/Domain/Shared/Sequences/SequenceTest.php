<?php

namespace Tests\Feature\Domain\Shared\Sequences;

use App\Domain\Shared\Exceptions\NotInTransaction;
use App\Domain\Shared\Sequences\Sequence;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class SequenceTest extends TestCase
{
    /** Not transaction-wrapped: these tests assert behaviour outside a transaction. */
    use DatabaseTruncation;

    public function test_issues_contiguous_numbers_starting_at_one(): void
    {
        $sequence = $this->app->make(Sequence::class);

        $numbers = DB::transaction(fn () => [
            $sequence->next('invoice', '2026'),
            $sequence->next('invoice', '2026'),
            $sequence->next('invoice', '2026'),
        ]);

        $this->assertSame([1, 2, 3], $numbers);
    }

    public function test_a_rolled_back_transaction_leaves_no_gap(): void
    {
        $sequence = $this->app->make(Sequence::class);
        DB::transaction(fn () => $sequence->next('receipt', '2026'));

        try {
            DB::transaction(function () use ($sequence): void {
                $sequence->next('receipt', '2026');

                throw new RuntimeException('Payment failed validation.');
            });
        } catch (RuntimeException) {
        }

        $this->assertSame(2, DB::transaction(fn () => $sequence->next('receipt', '2026')));
    }

    public function test_keeps_separate_counters_per_period_and_business(): void
    {
        $sequence = $this->app->make(Sequence::class);

        $numbers = DB::transaction(fn () => [
            $sequence->next('invoice', '2026', businessId: 1),
            $sequence->next('invoice', '2027', businessId: 1),
            $sequence->next('invoice', '2026', businessId: 2),
            $sequence->next('invoice', '2026', businessId: 1),
        ]);

        $this->assertSame([1, 1, 1, 2], $numbers);
    }

    public function test_refuses_to_issue_numbers_outside_a_transaction(): void
    {
        $this->expectException(NotInTransaction::class);

        $this->app->make(Sequence::class)->next('invoice', '2026');
    }
}
