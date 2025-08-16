<?php

use Illuminate\Support\Facades\Route;
use Modules\TgBot\Http\Controllers\TgBotController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tgbots', TgBotController::class)->names('tgbot');
});
