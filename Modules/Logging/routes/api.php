<?php

use Illuminate\Support\Facades\Route;
use Modules\Logging\Http\Controllers\LoggingController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('loggings', LoggingController::class)->names('logging');
});
