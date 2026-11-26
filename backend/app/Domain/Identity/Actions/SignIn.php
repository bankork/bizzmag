<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Auth\LoginThrottle;
use App\Domain\Identity\Exceptions\AccountDisabled;
use App\Domain\Identity\Exceptions\InvalidCredentials;
use App\Domain\Identity\Exceptions\LoginLocked;
use App\Domain\Identity\Exceptions\NoBusinessAccess;
use App\Domain\Identity\Support\IdentifierResolver;
use App\Domain\Identity\Support\Membership;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Checks sign-in details (FR-AUTH-001) with lockout (SEC-017) and records
 * the outcome (FR-AUTH-006). Starting the session is the HTTP layer's job.
 *
 * The answer for an unknown account and for a wrong password is the same,
 * and takes the same time, so the response never reveals who has an account.
 */
final class SignIn
{
    public function __construct(
        private readonly LoginThrottle $throttle,
        private readonly IdentifierResolver $identifiers,
        private readonly Membership $membership,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @return array{user: User, business_id: int}
     */
    public function __invoke(string $identifier, string $password, string $ip): array
    {
        $key = $this->identifiers->normalise($identifier);

        if (($retryAfter = $this->throttle->lockedFor($key, $ip)) !== null) {
            throw new LoginLocked(context: ['retry_after_seconds' => $retryAfter]);
        }

        $user = $this->identifiers->find($identifier);
        $passwordMatches = $user !== null
            ? Hash::check($password, $user->password)
            : Hash::check($password, $this->dummyHash());

        if ($user === null || ! $passwordMatches) {
            $this->fail($key, $ip, $user);
        }

        /** @var User $user */
        $businessId = $this->membership->activeBusinessId($user);

        if (! $user->is_active) {
            $this->audit->record(new AuditEntry('auth.login_refused', 'user', $user->id, reason: 'Account disabled', actorId: $user->id, businessId: $businessId));

            throw new AccountDisabled;
        }

        if ($businessId === null) {
            throw new NoBusinessAccess;
        }

        $this->throttle->clear($key, $ip);

        if (Hash::needsRehash($user->password)) {
            $user->password = $password;
        }

        $user->last_login_at = now();
        $user->save();

        $this->audit->record(new AuditEntry('auth.login', 'user', $user->id, actorId: $user->id, businessId: $businessId));

        return ['user' => $user, 'business_id' => $businessId];
    }

    private function fail(string $key, string $ip, ?User $user): never
    {
        $lockedFor = $this->throttle->recordFailure($key, $ip);
        $businessId = $user === null ? null : $this->membership->activeBusinessId($user);

        $this->audit->record(new AuditEntry(
            'auth.login_failed',
            $user === null ? null : 'user',
            $user?->id,
            after: $user === null ? ['account' => 'unknown'] : null,
            businessId: $businessId,
        ));

        if ($lockedFor !== null) {
            $this->audit->record(new AuditEntry(
                'auth.locked',
                $user === null ? null : 'user',
                $user?->id,
                after: ['locked_seconds' => $lockedFor],
                businessId: $businessId,
            ));

            throw new LoginLocked(context: ['retry_after_seconds' => $lockedFor]);
        }

        throw new InvalidCredentials;
    }

    private function dummyHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make('timing-equaliser');
    }
}
