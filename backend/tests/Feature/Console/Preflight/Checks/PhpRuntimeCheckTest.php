<?php

namespace Tests\Feature\Console\Preflight\Checks;

use App\Console\Preflight\Checks\PhpRuntimeCheck;
use App\Console\Preflight\CheckStatus;
use Tests\TestCase;

class PhpRuntimeCheckTest extends TestCase
{
    private const ALL_EXTENSIONS = [
        'ctype', 'curl', 'dom', 'fileinfo', 'filter', 'gd', 'hash', 'mbstring', 'openssl',
        'pcre', 'pdo', 'pdo_mysql', 'session', 'tokenizer', 'xml', 'zip', 'intl', 'opcache',
    ];

    public function test_passes_on_php_8_3_with_every_extension(): void
    {
        $results = (new PhpRuntimeCheck('8.3.12', self::ALL_EXTENSIONS))->run();

        $this->assertSame([CheckStatus::Pass, CheckStatus::Pass], array_map(fn ($result) => $result->status, $results));
    }

    public function test_fails_on_php_older_than_8_3(): void
    {
        $results = (new PhpRuntimeCheck('8.2.20', self::ALL_EXTENSIONS))->run();

        $this->assertSame(CheckStatus::Fail, $results[0]->status);
        $this->assertSame('PHP 8.2.20 is older than 8.3.0.', $results[0]->detail);
    }

    public function test_fails_and_names_missing_required_extensions(): void
    {
        $extensions = array_values(array_diff(self::ALL_EXTENSIONS, ['gd', 'zip']));

        $results = (new PhpRuntimeCheck('8.3.12', $extensions))->run();

        $this->assertSame(CheckStatus::Fail, $results[1]->status);
        $this->assertSame('Missing: gd, zip', $results[1]->detail);
    }

    public function test_only_warns_for_missing_recommended_extensions(): void
    {
        $extensions = array_values(array_diff(self::ALL_EXTENSIONS, ['intl']));

        $results = (new PhpRuntimeCheck('8.3.12', $extensions))->run();

        $this->assertSame(CheckStatus::Warn, $results[2]->status);
        $this->assertSame('Not loaded: intl', $results[2]->detail);
    }
}
