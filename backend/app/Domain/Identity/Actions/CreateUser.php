<?php

namespace App\Domain\Identity\Actions;

use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Support\UserSnapshot;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a staff account in the current business (FR-AUTH-003), with its
 * roles and, for people who do salon work, an employee profile.
 * The person must choose their own password at first sign-in.
 */
final class CreateUser
{
    public function __construct(
        private readonly CurrentBusiness $business,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  list<int>  $roleIds
     * @param  array{display_name: string, job_title?: ?string}|null  $profile
     */
    public function __invoke(
        string $name,
        ?string $email,
        ?string $phone,
        string $password,
        array $roleIds,
        ?array $profile,
    ): User {
        $businessId = $this->business->require();

        return DB::transaction(function () use ($businessId, $name, $email, $phone, $password, $roleIds, $profile): User {
            $user = User::query()->create([
                'name' => $name,
                'email' => $email === null ? null : mb_strtolower($email),
                'phone' => $phone,
                'password' => $password,
                'must_change_password' => true,
            ]);

            $user->businesses()->attach($businessId, ['is_active' => true]);
            $user->syncRoles(Role::query()->forBusiness($businessId)->whereKey($roleIds)->get());

            if ($profile !== null) {
                EmployeeProfile::query()->create([
                    'user_id' => $user->id,
                    'display_name' => $profile['display_name'],
                    'job_title' => $profile['job_title'] ?? null,
                ]);
            }

            $this->audit->record(new AuditEntry('user.created', 'user', $user->id, after: UserSnapshot::of($user)));

            return $user->refresh();
        });
    }
}
