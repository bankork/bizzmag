<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1
|--------------------------------------------------------------------------
|
| Every route lives under /api/v1. Each module has its own route file in
| routes/api/v1/<module>.php, loaded here in alphabetical order.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    foreach (glob(__DIR__.'/api/v1/*.php') ?: [] as $routeFile) {
        require $routeFile;
    }
});
