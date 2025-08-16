<?php

use Illuminate\Support\Facades\Route;
use Modules\QrGenerator\Http\Controllers\QrGeneratorController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('qrgenerators', QrGeneratorController::class)->names('qrgenerator');
});
