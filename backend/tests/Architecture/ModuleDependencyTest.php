<?php

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Enforces the module dependency graph (Stage 3 §4.2, ADR-03).
 *
 * A module may import only the modules listed for it. Reverse effects must
 * use domain events. Domain code may never import the HTTP layer.
 */
class ModuleDependencyTest extends TestCase
{
    /**
     * @var array<string, list<string>|'*'>
     */
    private const ALLOWED = [
        'Shared' => [],
        'Identity' => ['Shared'],
        'Business' => ['Shared', 'Identity'],
        'Audit' => ['Shared'],
        'Customers' => ['Shared', 'Identity', 'Business'],
        'Catalogue' => ['Shared', 'Identity', 'Business'],
        'Orders' => ['Shared', 'Identity', 'Business', 'Customers', 'Catalogue'],
        'Execution' => ['Shared', 'Identity', 'Business', 'Catalogue', 'Orders'],
        'Billing' => ['Shared', 'Identity', 'Business', 'Customers', 'Catalogue', 'Orders', 'Execution'],
        'Payments' => ['Shared', 'Identity', 'Business', 'Orders', 'Billing'],
        'Closing' => ['Shared', 'Identity', 'Business', 'Payments'],
        'Performance' => ['Shared', 'Identity', 'Business', 'Orders', 'Execution', 'Billing'],
        'Bonus' => ['Shared', 'Identity', 'Business', 'Performance'],
        'Documents' => ['Shared', 'Identity', 'Business', 'Customers', 'Orders', 'Billing', 'Payments'],
        'Imports' => ['Shared', 'Identity', 'Business', 'Customers', 'Catalogue'],
        'Notifications' => '*',
        'Reporting' => '*',
    ];

    public function test_every_domain_folder_is_a_declared_module(): void
    {
        $folders = array_map('basename', glob($this->domainPath().'/*', GLOB_ONLYDIR) ?: []);

        $this->assertSame([], array_values(array_diff($folders, array_keys(self::ALLOWED))));
    }

    public function test_modules_import_only_their_allowed_dependencies(): void
    {
        $violations = [];

        foreach ($this->domainFiles() as $module => $files) {
            foreach ($files as $file) {
                foreach ($this->importedModules($file) as $imported) {
                    if ($imported !== $module && ! $this->isAllowed($module, $imported)) {
                        $violations[] = "{$module} imports {$imported} in ".basename($file);
                    }
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_domain_code_never_imports_the_http_layer(): void
    {
        $violations = [];

        foreach ($this->domainFiles() as $files) {
            foreach ($files as $file) {
                if (preg_match('/^use\s+App\\\\Http\\\\/m', (string) file_get_contents($file)) === 1) {
                    $violations[] = basename($file);
                }
            }
        }

        $this->assertSame([], $violations);
    }

    public function test_the_declared_graph_has_no_cycles(): void
    {
        $visiting = [];
        $visited = [];
        $cycle = null;

        $visit = function (string $module, array $path) use (&$visit, &$visiting, &$visited, &$cycle): void {
            if (isset($visited[$module]) || $cycle !== null) {
                return;
            }

            if (isset($visiting[$module])) {
                $cycle = implode(' -> ', [...$path, $module]);

                return;
            }

            $visiting[$module] = true;

            $dependencies = self::ALLOWED[$module];

            foreach (is_array($dependencies) ? $dependencies : [] as $dependency) {
                $visit($dependency, [...$path, $module]);
            }

            unset($visiting[$module]);
            $visited[$module] = true;
        };

        foreach (array_keys(self::ALLOWED) as $module) {
            $visit($module, []);
        }

        $this->assertNull($cycle);
    }

    private function isAllowed(string $module, string $imported): bool
    {
        $allowed = self::ALLOWED[$module] ?? [];

        return $allowed === '*' || in_array($imported, $allowed, true);
    }

    /**
     * @return list<string>
     */
    private function importedModules(string $file): array
    {
        preg_match_all('/^use\s+App\\\\Domain\\\\(\w+)\\\\/m', (string) file_get_contents($file), $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @return array<string, list<string>>
     */
    private function domainFiles(): array
    {
        $files = [];

        foreach (glob($this->domainPath().'/*', GLOB_ONLYDIR) ?: [] as $folder) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder, RecursiveDirectoryIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                if ($file->getExtension() === 'php') {
                    $files[basename($folder)][] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function domainPath(): string
    {
        return dirname(__DIR__, 2).'/app/Domain';
    }
}
