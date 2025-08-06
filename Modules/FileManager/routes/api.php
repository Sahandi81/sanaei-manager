<?php

use Illuminate\Support\Facades\Route;
use Modules\FileManager\app\Http\Controllers\FileManagerController;

/*
 *--------------------------------------------------------------------------
 * API Routes
 *--------------------------------------------------------------------------
 *
 * Here is where you can register API routes for your application. These
 * routes are loaded by the RouteServiceProvider within a group which
 * is assigned the "api" middleware group. Enjoy building your API!
 *
*/

Route::group(['prefix' => '/v1/crew/panel', 'as' => 'v1.crew.file_manager.', 'middleware' => 'auth:crew-api'], function () {
	Route::delete('destroy',						[FileManagerController::class, 'destroy'])						->name('destroy');
});


