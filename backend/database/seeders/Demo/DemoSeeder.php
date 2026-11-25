<?php

namespace Database\Seeders\Demo;

use App\Models\User;
use Database\Seeders\Demo\Support\DemoData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds the fictional Nigerian demo salon (Stage 4 §10.2).
 *
 * Each phase extends this seeder for the tables it introduces. Phase 0
 * seeds only staff login accounts; roles, catalogue, customers and
 * activity history are added from Phase 1 onwards.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedStaffAccounts();
    }

    private function seedStaffAccounts(): void
    {
        $password = Hash::make(DemoData::salon()['demo_password']);

        foreach (DemoData::staff() as $member) {
            User::query()->updateOrCreate(
                ['email' => $member['email']],
                ['name' => $member['name'], 'password' => $password, 'email_verified_at' => now()],
            );
        }
    }
}
