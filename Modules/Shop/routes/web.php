<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\Http\Controllers\OrderController;
use Modules\Shop\Http\Controllers\ProductController;



Route::group(['prefix' => '/shop', 'as' => 'shop.', 'middleware' => 'auth:sanctum'], function (){
	Route::prefix('/products')->as('products.')->group(function (){

		Route::get('/list',										[ProductController::class, 'index'])				->name('index');
		Route::get('/create',									[ProductController::class, 'create'])				->name('create');
		Route::post('/store',									[ProductController::class, 'store'])				->name('store');
		Route::get('/edit/{product}',							[ProductController::class, 'edit'])					->name('edit');
		Route::any('/syncConfigs/{product}',					[ProductController::class, 'syncConfigs'])			->name('syncConfigs');
		Route::post('/update/{product}',						[ProductController::class, 'update'])				->name('update');
		Route::delete('/destroy/{product}',						[ProductController::class, 'destroy'])				->name('destroy');
	});


	Route::prefix('/orders')->as('orders.')->group(function (){
		Route::post('/store',									[OrderController::class, 'store'])					->name('store');
		Route::get('/subs/{subs}',								[OrderController::class, 'subs'])					->name('subs')->withoutMiddleware('auth:sanctum');
	});
});

