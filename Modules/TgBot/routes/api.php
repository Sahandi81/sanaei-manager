<?php

use Illuminate\Support\Facades\Route;
use Modules\TgBot\Http\Controllers\TgBotController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('tgbots', TgBotController::class)->names('tgbot');
});
