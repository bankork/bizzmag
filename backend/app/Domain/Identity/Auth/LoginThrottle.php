<?php

namespace App\Domain\Identity\Auth;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Sign-in lockout (SEC-017).
 *
 * - 5 failures for the same account from the same IP address within
 *   15 minutes lock that pair for 15 minutes. Locking the pair rather than
 *   the account means a stranger elsewhere cannot lock the owner out.
 * - 20 failures from one IP address, across any accounts, lock that
 *   address for 15 minutes.
 *
 * Counters live in the application cache (the database store by default),
 * so they are shared by every PHP process.
 */
final class LoginThrottle
{
    public const PAIR_LIMIT = 5;

    public const IP_LIMIT = 20;

    public const WINDOW_SECONDS = 900;

    public const LOCK_SECONDS = 900;

    /**
     * Seconds until the lock ends, or null when sign-in may be attempted.
     */
    public function lockedFor(string $identifier, string $ip): ?int
    {
        $until = max(
            (int) $this->cache()->get($this->lockKey($this->pairKey($identifier, $ip)), 0),
            (int) $this->cache()->get($this->lockKey($this->ipKey($ip)), 0),
        );

        $remaining = $until - now()->getTimestamp();

        return $remaining > 0 ? $remaining : null;
    }

    /**
     * Count a failure. Returns the lock length in seconds when this failure
     * caused a lock, otherwise null.
     */
    public function recordFailure(string $identifier, string $ip): ?int
    {
        $pairLocked = $this->hit($this->pairKey($identifier, $ip), self::PAIR_LIMIT);
        $ipLocked = $this->hit($this->ipKey($ip), self::IP_LIMIT);

        return $pairLocked || $ipLocked ? self::LOCK_SECONDS : null;
    }

    public function clear(string $identifier, string $ip): void
    {
        $key = $this->pairKey($identifier, $ip);
        $this->cache()->forget($key);
        $this->cache()->forget($this->lockKey($key));
    }

    /**
     * Lift the lock on an IP address.
     */
    public function clearIp(string $ip): void
    {
        $key = $this->ipKey($ip);
        $this->cache()->forget($key);
        $this->cache()->forget($this->lockKey($key));
    }

    private function hit(string $key, int $limit): bool
    {
        $window = $this->cache()->get($key);
        $now = now()->getTimestamp();

        if (! is_array($window) || $now >= $window['started'] + self::WINDOW_SECONDS) {
            $window = ['started' => $now, 'count' => 0];
        }

        $window['count']++;

        if ($window['count'] >= $limit) {
            $this->cache()->put($this->lockKey($key), $now + self::LOCK_SECONDS, self::LOCK_SECONDS);
            $this->cache()->forget($key);

            return true;
        }

        $this->cache()->put($key, $window, self::WINDOW_SECONDS);

        return false;
    }

    private function pairKey(string $identifier, string $ip): string
    {
        return 'login:pair:'.hash('sha256', mb_strtolower($identifier).'|'.$ip);
    }

    private function ipKey(string $ip): string
    {
        return 'login:ip:'.hash('sha256', $ip);
    }

    private function lockKey(string $key): string
    {
        return $key.':lock';
    }

    private function cache(): Repository
    {
        return Cache::store();
    }
}
