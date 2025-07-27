<?php

use Illuminate\Support\Facades\Route;
use Modules\Server\Http\Controllers\ServerController;

Route::group(['prefix' => '/servers', 'as' => 'servers.', 'middleware' => 'auth:sanctum'], function (){
	Route::get('/list',											[ServerController::class, 'index'])					->name('index');
	Route::get('/create',										[ServerController::class, 'create'])				->name('create');
	Route::post('/store',										[ServerController::class, 'store'])					->name('store');
	Route::get('/edit',											[ServerController::class, 'edit'])					->name('edit');
	Route::post('/update',										[ServerController::class, 'update'])				->name('update');
});
