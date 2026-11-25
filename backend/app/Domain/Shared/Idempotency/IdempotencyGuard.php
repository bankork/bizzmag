<?php

namespace App\Domain\Shared\Idempotency;

use App\Domain\Shared\Exceptions\IdempotencyMismatch;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use LogicException;

/**
 * Runs an operation at most once per (scope, key) (TDR-09, SRS §7.7.4).
 *
 * The key row is inserted first, inside the same transaction as the
 * operation. A second request with the same key blocks on the unique index
 * until the first commits, then reads the stored result and replays it. If
 * the first transaction rolls back, the key row disappears with it and the
 * second request runs normally.
 */
final class IdempotencyGuard
{
    public function __construct(private readonly ConnectionInterface $db) {}

    /**
     * @param  string  $scope  Who the key belongs to, for example "user:12".
     * @param  array<string, mixed>  $payload  The request data that must match on replay.
     * @param  Closure(): IdempotentResult  $operation
     */
    public function run(string $scope, string $key, array $payload, Closure $operation): IdempotentResult
    {
        $requestHash = self::hashPayload($payload);

        return $this->db->transaction(function () use ($scope, $key, $requestHash, $operation): IdempotentResult {
            try {
                $id = $this->db->table('idempotency_keys')->insertGetId([
                    'scope' => $scope,
                    'idempotency_key' => $key,
                    'request_hash' => $requestHash,
                    'created_at' => now(),
                ]);
            } catch (UniqueConstraintViolationException) {
                return $this->replayExisting($scope, $key, $requestHash);
            }

            $result = $operation();

            $this->db->table('idempotency_keys')->where('id', $id)->update([
                'status_code' => $result->status,
                'response' => json_encode($result->body, JSON_THROW_ON_ERROR),
                'completed_at' => now(),
            ]);

            return $result;
        }, 3);
    }

    /**
     * Hash a payload independently of key order.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function hashPayload(array $payload): string
    {
        return hash('sha256', json_encode(self::canonical($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function replayExisting(string $scope, string $key, string $requestHash): IdempotentResult
    {
        // A locking read sees the latest committed row even inside an older snapshot.
        $existing = $this->db->table('idempotency_keys')
            ->where('scope', $scope)
            ->where('idempotency_key', $key)
            ->sharedLock()
            ->first();

        if ($existing === null || $existing->status_code === null) {
            throw new LogicException('An idempotency key conflicted but no completed result was found.');
        }

        if (! hash_equals($existing->request_hash, $requestHash)) {
            throw new IdempotencyMismatch;
        }

        return IdempotentResult::replay(
            (int) $existing->status_code,
            json_decode($existing->response, true, flags: JSON_THROW_ON_ERROR),
        );
    }

    private static function canonical(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(self::canonical(...), $value);
    }
}
