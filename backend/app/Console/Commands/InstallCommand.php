<?php

namespace App\Console\Commands;

use App\Domain\Business\Actions\InstallBusiness;
use App\Domain\Business\Exceptions\AlreadyInstalled;
use App\Domain\Shared\Phone\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * First-run setup after the first deployment: creates the business, its
 * default roles, tax switched off, and the owner account.
 *
 * Interactive by default. For scripted installs pass the options and put
 * the password in the BIZZMAG_OWNER_PASSWORD environment variable, so it
 * never appears in the shell history.
 */
class InstallCommand extends Command
{
    protected $signature = 'app:install
                            {--business= : The salon name}
                            {--owner-name= : The owner\'s full name}
                            {--owner-email= : The owner\'s email (email or phone is required)}
                            {--owner-phone= : The owner\'s phone number, for example 0803 123 4567}
                            {--does-salon-work : The owner also does services and can be credited for them}';

    protected $description = 'Create the business and the first owner account';

    public function handle(InstallBusiness $install): int
    {
        $interactive = $this->input->isInteractive();

        $input = [
            'business' => $this->option('business') ?: ($interactive ? $this->ask('Salon name') : null),
            'name' => $this->option('owner-name') ?: ($interactive ? $this->ask('Owner\'s full name') : null),
            'email' => $this->option('owner-email') ?: ($interactive ? $this->ask('Owner\'s email (leave empty to use a phone number)') : null),
            'phone' => $this->option('owner-phone'),
            'password' => getenv('BIZZMAG_OWNER_PASSWORD') ?: null,
        ];

        if (empty($input['email']) && empty($input['phone']) && $interactive) {
            $input['phone'] = $this->ask('Owner\'s phone number');
        }

        if ($input['password'] === null && $interactive) {
            $input['password'] = $this->secret('Owner\'s password (at least 8 characters)');
            $confirm = $this->secret('Type the password again');

            if ($input['password'] !== $confirm) {
                $this->error('The passwords do not match. Nothing was created.');

                return self::FAILURE;
            }
        }

        $input['phone'] = empty($input['phone']) ? null : (PhoneNumber::normalise((string) $input['phone']) ?? $input['phone']);
        $input['email'] = empty($input['email']) ? null : mb_strtolower(trim((string) $input['email']));

        $validator = Validator::make($input, [
            'business' => ['required', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'required_without:phone', 'email:rfc', 'max:190', 'unique:users,email'],
            'phone' => ['nullable', 'required_without:email', 'regex:/^\+[1-9]\d{7,14}$/', 'unique:users,phone'],
            'password' => ['required', 'string', Password::defaults()],
        ], [
            'phone.regex' => 'Enter a valid phone number, for example 0803 123 4567.',
            'password.required' => 'Set BIZZMAG_OWNER_PASSWORD, or run the command interactively.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        try {
            ['business' => $business, 'owner' => $owner] = $install((string) $input['business'], [
                'name' => (string) $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'],
                'password' => (string) $input['password'],
                'does_salon_work' => (bool) $this->option('does-salon-work'),
            ]);
        } catch (AlreadyInstalled $exception) {
            $this->error($exception->getMessage().' Sign in as the owner to add staff.');

            return self::FAILURE;
        }

        $this->info("Installed {$business->name}.");
        $this->line('Owner: '.$owner->name.' ('.($owner->email ?? $owner->phone).')');
        $this->line('Tax is switched off. Turn it on in Admin > Settings > Tax if the salon charges VAT.');

        return self::SUCCESS;
    }
}
