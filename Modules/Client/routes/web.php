<?php

use Illuminate\Support\Facades\Route;
use Modules\Client\Http\Controllers\ClientController;

Route::group(['prefix' => '/clients', 'as' => 'clients.', 'middleware' => 'auth:sanctum'], function (){
	Route::get('/list',											[ClientController::class, 'index'])					->name('index');
	Route::get('/create',										[ClientController::class, 'create'])				->name('create');
	Route::post('/store',										[ClientController::class, 'store'])					->name('store');
	Route::get('/edit/{client}',								[ClientController::class, 'edit'])					->name('edit');
	Route::post('/update/{client}',								[ClientController::class, 'update'])				->name('update');
	Route::delete('/destroy/{client}',							[ClientController::class, 'destroy'])				->name('destroy');
});
