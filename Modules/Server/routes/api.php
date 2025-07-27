<?php

use Illuminate\Support\Facades\Route;
use Modules\Server\Http\Controllers\ServerController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('servers', ServerController::class)->names('server');
});
