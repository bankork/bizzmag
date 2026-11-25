<?php

namespace App\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\PreflightCheck;

/**
 * Folders Laravel must be able to write to.
 */
final class FilesystemCheck implements PreflightCheck
{
    /**
     * @param  list<string>|null  $paths  Injected in tests; defaults to Laravel's writable folders.
     */
    public function __construct(private readonly ?array $paths = null) {}

    public function run(): array
    {
        $paths = $this->paths ?? [
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('logs'),
            base_path('bootstrap/cache'),
        ];

        $notWritable = array_values(array_filter($paths, fn (string $path) => ! is_dir($path) || ! is_writable($path)));

        if ($notWritable === []) {
            return [CheckResult::pass('Writable folders', count($paths).' folders writable')];
        }

        $relative = array_map(fn (string $path) => str_replace(base_path().'/', '', $path), $notWritable);

        return [CheckResult::fail(
            'Writable folders',
            'Not writable: '.implode(', ', $relative),
            'Create the folders and make them writable by the web server user (chmod 775).',
        )];
    }
}
