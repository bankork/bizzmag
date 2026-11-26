<?php

namespace App\Console\Commands;

use App\Domain\Business\Models\Business;
use App\Domain\Identity\Actions\ProvisionDefaultRoles;
use Illuminate\Console\Command;

/**
 * Adds permissions introduced by a new release and gives them to the Owner
 * role. Roles the owner has edited are left alone. Run by the deploy script
 * after migrations.
 */
class SyncPermissionsCommand extends Command
{
    protected $signature = 'app:permissions:sync';

    protected $description = 'Create any new permissions and make sure every business has its default roles';

    public function handle(ProvisionDefaultRoles $provision): int
    {
        $count = 0;

        Business::query()->orderBy('id')->each(function (Business $business) use ($provision, &$count): void {
            $provision($business->id);
            $count++;
        });

        $this->info("Permissions are up to date for {$count} business(es).");

        return self::SUCCESS;
    }
}
