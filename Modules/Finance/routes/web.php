<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceController;
use Modules\Finance\Http\Controllers\TransactionController;

Route::group(['prefix' => '/finance', 'as' => 'finance.', 'middleware' => 'auth:sanctum'], function (){

	Route::prefix('/transactions')->as('transactions.')->group(function (){

		Route::get('/list',										[TransactionController::class, 'index'])			->name('index');
		Route::get('/create',									[TransactionController::class, 'create'])			->name('create');
		Route::post('/store',									[TransactionController::class, 'store'])			->name('store');
		Route::get('/edit/{transaction}',						[TransactionController::class, 'edit'])				->name('edit');
		Route::post('/update/{transaction}',					[TransactionController::class, 'update'])			->name('update');

	});

});
