<?php

namespace Database\Seeders\Demo;

use App\Domain\Business\Actions\InstallBusiness;
use App\Domain\Business\Models\Business;
use App\Domain\Identity\Actions\ProvisionDefaultRoles;
use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Models\User;
use Database\Seeders\Demo\Support\DemoData;
use Database\Seeders\Demo\Support\NigerianPhoneNumbers;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Seeds the fictional Nigerian demo salon (Stage 4 §10.2). Safe to run
 * again: it updates what exists and adds what is missing.
 *
 * Phase 1: the business and its profile, default roles, tax switched off,
 * and the 12 staff with their roles, phone numbers and employee profiles.
 * Everyone can sign in with their email or their phone number.
 */
class DemoSeeder extends Seeder
{
    /**
     * Fixed seed, so the demo phone numbers are the same on every machine.
     */
    private const PHONE_SEED = 2026;

    public function __construct(
        private readonly InstallBusiness $install,
        private readonly ProvisionDefaultRoles $provisionRoles,
        private readonly CurrentBusiness $business,
    ) {}

    public function run(): void
    {
        $salon = DemoData::salon();
        $staff = DemoData::staff();
        $owner = $staff[0];

        $business = Business::query()->where('name', $salon['name'])->first()
            ?? ($this->install)($salon['name'], [
                'name' => $owner['name'],
                'email' => $owner['email'],
                'phone' => null,
                'password' => $salon['demo_password'],
            ], allowAdditional: true)['business'];

        $business->fill([
            'phone' => $salon['phone'],
            'email' => $salon['email'],
            'address' => $salon['address'],
            'timezone' => $salon['timezone'],
            'receipt_footer' => $salon['receipt_footer'],
        ])->save();

        $this->business->within($business->id, fn () => $this->seedStaff($business, $staff, $salon['demo_password']));
    }

    /**
     * @param  list<array{key: string, name: string, email: string, role: string, skills: list<string>, apprentice?: bool}>  $staff
     */
    private function seedStaff(Business $business, array $staff, string $password): void
    {
        $roles = ($this->provisionRoles)($business->id);
        $phones = new NigerianPhoneNumbers(new Randomizer(new Mt19937(self::PHONE_SEED)));
        $hash = Hash::make($password);

        foreach ($staff as $member) {
            $phone = $phones->next();

            $user = User::query()->firstOrNew(['email' => $member['email']]);
            $user->forceFill([
                'name' => $member['name'],
                'phone' => $user->phone ?? $phone,
                'password' => $hash,
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            $user->businesses()->syncWithoutDetaching([$business->id => ['is_active' => true]]);
            $user->syncRoles([$roles[$member['role']]]);

            if ($member['role'] !== 'cashier') {
                EmployeeProfile::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'display_name' => strtok($member['name'], ' ') ?: $member['name'],
                        'job_title' => $this->jobTitle($member),
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @param  array{role: string, skills: list<string>, apprentice?: bool}  $member
     */
    private function jobTitle(array $member): string
    {
        if ($member['apprentice'] ?? false) {
            return 'Apprentice';
        }

        return match ($member['role']) {
            'owner' => 'Owner and stylist',
            'supervisor' => 'Supervisor and stylist',
            default => match ($member['skills'][0] ?? null) {
                'braids' => 'Braider',
                'nails' => 'Nail technician',
                'lashes' => 'Lash and makeup artist',
                default => 'Stylist',
            },
        };
    }
}
