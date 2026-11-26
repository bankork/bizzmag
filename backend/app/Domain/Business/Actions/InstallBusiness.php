<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Exceptions\AlreadyInstalled;
use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\TaxSetting;
use App\Domain\Identity\Actions\ProvisionDefaultRoles;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Permissions\SystemRole;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * First-run setup (`php artisan app:install`): the business, its default
 * roles, tax switched off (FR-TAX-001) and the first owner.
 */
final class InstallBusiness
{
    public function __construct(
        private readonly ProvisionDefaultRoles $provisionRoles,
        private readonly CurrentBusiness $business,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array{name: string, email: ?string, phone: ?string, password: string, does_salon_work?: bool}  $owner
     * @return array{business: Business, owner: User}
     */
    public function __invoke(string $businessName, array $owner, bool $allowAdditional = false): array
    {
        if (! $allowAdditional && Business::query()->exists()) {
            throw new AlreadyInstalled;
        }

        return DB::transaction(function () use ($businessName, $owner): array {
            $business = Business::query()->create([
                'name' => $businessName,
                'currency' => config('bizzmag.currency'),
                'timezone' => config('bizzmag.default_timezone'),
            ]);

            return $this->business->within($business->id, function () use ($business, $owner): array {
                $roles = ($this->provisionRoles)($business->id);

                TaxSetting::query()->create(['business_id' => $business->id]);

                $user = User::query()->create([
                    'name' => $owner['name'],
                    'email' => $owner['email'] === null ? null : mb_strtolower($owner['email']),
                    'phone' => $owner['phone'],
                    'password' => $owner['password'],
                    'password_changed_at' => now(),
                ]);

                $user->businesses()->attach($business->id, ['is_active' => true]);
                $user->assignRole($roles[SystemRole::Owner->value]);

                if ($owner['does_salon_work'] ?? false) {
                    EmployeeProfile::query()->create(['user_id' => $user->id, 'display_name' => $owner['name']]);
                }

                $this->audit->record(new AuditEntry(
                    'business.installed',
                    'business',
                    $business->id,
                    after: ['name' => $business->name, 'owner_user_id' => $user->id, 'tax_enabled' => false],
                    businessId: $business->id,
                ));

                return ['business' => $business->refresh(), 'owner' => $user->refresh()];
            });
        });
    }
}
