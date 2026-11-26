<?php

namespace App\Console\Commands;

use App\Domain\Identity\Actions\ResetPassword;
use App\Domain\Identity\Support\IdentifierResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Server-side fallback when the owner forgets their password or nobody
 * with user management can sign in. Sets a temporary password that must be
 * changed at the next sign-in. A sign-in lockout ends by itself after
 * 15 minutes.
 */
class ResetUserPasswordCommand extends Command
{
    protected $signature = 'app:user:reset-password {identifier : Email or phone number of the account}';

    protected $description = 'Give a user a temporary password (for when the owner is locked out)';

    public function handle(IdentifierResolver $identifiers, ResetPassword $reset): int
    {
        $user = $identifiers->find((string) $this->argument('identifier'));

        if ($user === null) {
            $this->error('No account uses that email or phone number.');

            return self::FAILURE;
        }

        $temporary = 'Temp-'.Str::password(10, symbols: false);
        $reset(null, $user, $temporary);

        $this->info("Temporary password for {$user->name}: {$temporary}");
        $this->line('They must choose a new password when they sign in. Their other sessions were signed out.');

        if (! $user->is_active) {
            $this->warn('This account is disabled. Enable it in Admin > Staff before they can sign in.');
        }

        return self::SUCCESS;
    }
}
