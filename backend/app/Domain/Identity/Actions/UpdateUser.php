<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Support\UserSnapshot;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Edits a staff member's details and employee profile. Only the fields
 * given are changed. Passing a profile of false turns salon work off.
 */
final class UpdateUser
{
    public function __construct(private readonly AuditRecorder $audit) {}

    /**
     * @param  array{name?: string, email?: ?string, phone?: ?string}  $details
     * @param  array{display_name?: string, job_title?: ?string, is_active?: bool}|null  $profile
     */
    public function __invoke(User $user, array $details, ?array $profile): User
    {
        return DB::transaction(function () use ($user, $details, $profile): User {
            $before = UserSnapshot::of($user);

            if (array_key_exists('email', $details) && $details['email'] !== null) {
                $details['email'] = mb_strtolower($details['email']);
            }

            $user->fill($details)->save();

            if ($profile !== null) {
                $existing = $user->employeeProfile;

                if ($existing === null) {
                    EmployeeProfile::query()->create([
                        'user_id' => $user->id,
                        'display_name' => $profile['display_name'] ?? $user->name,
                        'job_title' => $profile['job_title'] ?? null,
                        'is_active' => $profile['is_active'] ?? true,
                    ]);
                } else {
                    $existing->fill($profile)->save();
                }

                $user->unsetRelation('employeeProfile');
            }

            $after = UserSnapshot::of($user);

            if ($before !== $after) {
                $this->audit->record(new AuditEntry('user.updated', 'user', $user->id, before: $before, after: $after));
            }

            return $user;
        });
    }
}
