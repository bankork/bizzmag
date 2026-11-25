<?php

namespace App\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\PreflightCheck;

/**
 * PHP version and the extensions Laravel 13 and this application need.
 */
final class PhpRuntimeCheck implements PreflightCheck
{
    /**
     * Required by Laravel itself, PDF generation (gd), backups (zip) and MySQL (pdo_mysql).
     *
     * @var list<string>
     */
    private const REQUIRED_EXTENSIONS = [
        'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'hash', 'mbstring',
        'openssl', 'pcre', 'pdo', 'pdo_mysql', 'session', 'tokenizer', 'xml', 'zip',
    ];

    /** @var list<string> */
    private const RECOMMENDED_EXTENSIONS = ['intl', 'opcache'];

    /**
     * @param  list<string>|null  $loadedExtensions  Injected in tests; defaults to the running PHP.
     */
    public function __construct(
        private readonly ?string $phpVersion = null,
        private readonly ?array $loadedExtensions = null,
    ) {}

    public function run(): array
    {
        $version = $this->phpVersion ?? PHP_VERSION;
        $minimum = config('bizzmag.minimums.php');
        $loaded = array_map('strtolower', $this->loadedExtensions ?? $this->runtimeExtensions());

        $results = [
            version_compare($version, $minimum, '>=')
                ? CheckResult::pass('PHP version', "PHP {$version}")
                : CheckResult::fail('PHP version', "PHP {$version} is older than {$minimum}.", "Select PHP {$minimum} or newer for this site (cPanel: MultiPHP Manager)."),
        ];

        $missing = array_values(array_diff(self::REQUIRED_EXTENSIONS, $loaded));
        $results[] = $missing === []
            ? CheckResult::pass('PHP extensions', count(self::REQUIRED_EXTENSIONS).' required extensions loaded')
            : CheckResult::fail('PHP extensions', 'Missing: '.implode(', ', $missing), 'Enable them in cPanel: Select PHP Version > Extensions, or install the php8.3-* packages on a VPS.');

        $missingRecommended = array_values(array_diff(self::RECOMMENDED_EXTENSIONS, $loaded));

        if ($missingRecommended !== []) {
            $results[] = CheckResult::warn('Recommended extensions', 'Not loaded: '.implode(', ', $missingRecommended), 'Enable them for better performance and formatting.');
        }

        return $results;
    }

    /**
     * @return list<string>
     */
    private function runtimeExtensions(): array
    {
        $extensions = get_loaded_extensions();

        if (function_exists('opcache_get_status') || extension_loaded('Zend OPcache')) {
            $extensions[] = 'opcache';
        }

        return $extensions;
    }
}
