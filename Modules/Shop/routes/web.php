<?php

use Illuminate\Support\Facades\Route;
use Modules\Shop\Http\Controllers\ProductController;



Route::group(['prefix' => '/products', 'as' => 'products.', 'middleware' => 'auth:sanctum'], function (){
	Route::get('/list',											[ProductController::class, 'index'])				->name('index');
	Route::get('/create',										[ProductController::class, 'create'])				->name('create');
	Route::post('/store',										[ProductController::class, 'store'])				->name('store');
	Route::get('/edit/{product}',								[ProductController::class, 'edit'])					->name('edit');
	Route::post('/update/{product}',							[ProductController::class, 'update'])				->name('update');
	Route::delete('/destroy/{product}',							[ProductController::class, 'destroy'])				->name('destroy');

});

