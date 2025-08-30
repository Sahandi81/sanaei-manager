<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\CardController;
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


	Route::prefix('/cards')->as('cards.')->group(function (){

		Route::get('/list',										[CardController::class, 'index'])					->name('index');
		Route::get('/create',									[CardController::class, 'create'])					->name('create');
		Route::post('/store',									[CardController::class, 'store'])					->name('store');
		Route::get('/edit/{card}',								[CardController::class, 'edit'])					->name('edit');
		Route::post('/update/{card}',							[CardController::class, 'update'])					->name('update');

	});

});
