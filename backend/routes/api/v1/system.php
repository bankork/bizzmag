<?php

use App\Http\Controllers\Api\V1\System\MetaController;
use Illuminate\Support\Facades\Route;

Route::get('meta', MetaController::class)->name('system.meta');
