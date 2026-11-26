<?php

namespace App\Console\Commands;

use Database\Seeders\Demo\DemoSeeder;
use Database\Seeders\Demo\Support\DemoData;
use Illuminate\Console\Command;

/**
 * Loads the fictional Nigerian demo salon. Never runs in production.
 */
class DemoCommand extends Command
{
    protected $signature = 'app:demo
                            {--fresh : Drop every table and rebuild the database before seeding}
                            {--force : Do not ask for confirmation}';

    protected $description = 'Load the Adunni Hair & Beauty Studio demo data (development and staging only)';

    public function handle(): int
    {
        if ($this->laravel->isProduction()) {
            $this->error('Demo data cannot be loaded in production.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $database = config('database.connections.'.config('database.default').'.database');

            if (! $this->option('force') && ! $this->confirm("This deletes ALL data in the \"{$database}\" database. Continue?")) {
                $this->comment('Nothing changed.');

                return self::FAILURE;
            }

            $this->call('migrate:fresh', ['--force' => true]);
        }

        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->newLine();
        $this->info('Demo salon loaded: '.DemoData::salon()['name']);
        $this->line('Sign in with any staff email or phone number (see docs/demo-data.md). Password: '.DemoData::salon()['demo_password']);

        return self::SUCCESS;
    }
}
