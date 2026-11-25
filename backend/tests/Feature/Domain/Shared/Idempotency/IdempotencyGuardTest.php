<?php

namespace Tests\Feature\Domain\Shared\Idempotency;

use App\Domain\Shared\Exceptions\IdempotencyMismatch;
use App\Domain\Shared\Idempotency\IdempotencyGuard;
use App\Domain\Shared\Idempotency\IdempotentResult;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class IdempotencyGuardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_runs_the_operation_once_and_replays_the_stored_result(): void
    {
        $guard = $this->app->make(IdempotencyGuard::class);
        $runs = 0;
        $operation = function () use (&$runs): IdempotentResult {
            $runs++;

            return IdempotentResult::fresh(201, ['payment_id' => 3001, 'amount_kobo' => 1000000]);
        };

        $first = $guard->run('user:7', 'pay-3f1c-0001', ['amount_kobo' => 1000000, 'method' => 'cash'], $operation);
        $second = $guard->run('user:7', 'pay-3f1c-0001', ['method' => 'cash', 'amount_kobo' => 1000000], $operation);

        $this->assertSame(1, $runs);
        $this->assertFalse($first->replayed);
        $this->assertTrue($second->replayed);
        $this->assertSame(201, $second->status);
        $this->assertSame(['payment_id' => 3001, 'amount_kobo' => 1000000], $second->body);
    }

    public function test_rejects_the_same_key_with_a_different_payload(): void
    {
        $guard = $this->app->make(IdempotencyGuard::class);
        $guard->run('user:7', 'pay-3f1c-0002', ['amount_kobo' => 1000000], fn () => IdempotentResult::fresh(201, []));

        $this->expectException(IdempotencyMismatch::class);

        $guard->run('user:7', 'pay-3f1c-0002', ['amount_kobo' => 2000000], fn () => IdempotentResult::fresh(201, []));
    }

    public function test_the_same_key_in_another_scope_is_independent(): void
    {
        $guard = $this->app->make(IdempotencyGuard::class);
        $runs = 0;
        $operation = function () use (&$runs): IdempotentResult {
            $runs++;

            return IdempotentResult::fresh(200, []);
        };

        $guard->run('user:7', 'shared-key-0001', [], $operation);
        $guard->run('user:8', 'shared-key-0001', [], $operation);

        $this->assertSame(2, $runs);
    }

    public function test_a_failed_operation_leaves_no_key_so_a_retry_runs(): void
    {
        $guard = $this->app->make(IdempotencyGuard::class);

        try {
            $guard->run('user:7', 'pay-3f1c-0003', [], fn () => throw new RuntimeException('Balance exceeded.'));
        } catch (RuntimeException) {
        }

        $retry = $guard->run('user:7', 'pay-3f1c-0003', [], fn () => IdempotentResult::fresh(201, ['ok' => true]));

        $this->assertFalse($retry->replayed);
        $this->assertDatabaseCount('idempotency_keys', 1);
    }
}
