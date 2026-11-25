<?php

namespace Tests\Feature\Console\Preflight\Checks;

use App\Console\Preflight\Checks\FilesystemCheck;
use App\Console\Preflight\CheckStatus;
use Tests\TestCase;

class FilesystemCheckTest extends TestCase
{
    public function test_passes_when_every_folder_is_writable(): void
    {
        $results = (new FilesystemCheck([storage_path('logs')]))->run();

        $this->assertSame(CheckStatus::Pass, $results[0]->status);
    }

    public function test_fails_and_names_a_missing_folder(): void
    {
        $results = (new FilesystemCheck([storage_path('logs'), base_path('does-not-exist')]))->run();

        $this->assertSame(CheckStatus::Fail, $results[0]->status);
        $this->assertSame('Not writable: does-not-exist', $results[0]->detail);
    }
}
