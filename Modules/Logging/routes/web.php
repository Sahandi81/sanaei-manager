<?php

use Illuminate\Support\Facades\Route;
use Modules\Logging\Http\Controllers\LoggingController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('loggings', LoggingController::class)->names('logging');
});
