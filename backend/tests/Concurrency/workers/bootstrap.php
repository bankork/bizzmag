<?php

/**
 * Boots the application inside a separate PHP process for concurrency tests.
 *
 * The parent test passes the testing database through environment variables,
 * which take precedence over .env, so workers never touch the dev database.
 */

use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (config('database.connections.'.config('database.default').'.database') !== 'bizzmag_testing'
    && getenv('CONCURRENCY_ALLOW_DATABASE') !== config('database.connections.'.config('database.default').'.database')) {
    fwrite(STDERR, "Refusing to run concurrency workers outside the testing database.\n");
    exit(2);
}

/**
 * Wait until the shared start time so all workers begin together.
 */
function waitForStart(): void
{
    $startAt = (float) getenv('CONCURRENCY_START_AT');

    while (microtime(true) < $startAt) {
        usleep(1000);
    }
}

return $app;
