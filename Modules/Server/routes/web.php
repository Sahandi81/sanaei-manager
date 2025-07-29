<?php

use Illuminate\Support\Facades\Route;
use Modules\Server\Http\Controllers\ServerController;

Route::group(['prefix' => '/servers', 'as' => 'servers.', 'middleware' => 'auth:sanctum'], function (){
	Route::get('/list',											[ServerController::class, 'index'])					->name('index');
	Route::get('/create',										[ServerController::class, 'create'])				->name('create');
	Route::post('/store',										[ServerController::class, 'store'])					->name('store');
	Route::get('/edit/{server}',								[ServerController::class, 'edit'])					->name('edit');
	Route::post('/update/{server}',								[ServerController::class, 'update'])				->name('update');
	Route::delete('/destroy/{server}',							[ServerController::class, 'destroy'])				->name('destroy');

});
