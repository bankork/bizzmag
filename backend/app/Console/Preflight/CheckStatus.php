<?php

namespace App\Console\Preflight;

enum CheckStatus: string
{
    case Pass = 'pass';

    /** Not blocking, but worth fixing. */
    case Warn = 'warn';

    /** The application will not work correctly on this host. */
    case Fail = 'fail';
}
