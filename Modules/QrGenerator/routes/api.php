<?php

use Illuminate\Support\Facades\Route;
use Modules\QrGenerator\Http\Controllers\QrGeneratorController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('qrgenerators', QrGeneratorController::class)->names('qrgenerator');
});
