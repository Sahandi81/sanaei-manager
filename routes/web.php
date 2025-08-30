<?php

use App\Http\Controllers\Auth\LoginCover;
use App\Http\Controllers\Auth\RegisterCover;
use App\Http\Controllers\Auth\ResetPasswordCover;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Modules\Server\Services\SyncUserService;
use Modules\Shop\Console\Commands\DeactivateExceededTrafficOrders;
use Modules\Shop\Services\ClientProvisioningService;
use Modules\Shop\Services\OrderActivationService;

Route::get('/', function (){
	return redirect()->route('panel.index');
});

Route::get('login', 											[LoginCover::class, 'index'])						->name('login');
Route::post('login', 											[AuthController::class, 'login'])					->name('post_login');
Route::get('register',	 										[RegisterCover::class, 'index'])					->name('register');
Route::get('reset-password', 									[ResetPasswordCover::class, 'index'])				->name('reset_password');


Route::group(['prefix' => '/panel', 'as' => 'panel.', 'middleware' => 'auth:sanctum'], function (){
	Route::get('/',												[PanelController::class, 'index'])					->name('index');

	Route::group(['prefix' => '/users', 'as' => 'users.'], function (){
		Route::get('/list',										[UserController::class, 'index'])					->name('index');
		Route::get('/create',									[UserController::class, 'create'])					->name('create');
		Route::post('/store',									[UserController::class, 'store'])					->name('store');
		Route::get('/edit/{client}',							[UserController::class, 'edit'])					->name('edit');
		Route::post('/update/{client}',							[UserController::class, 'update'])					->name('update');
		Route::delete('/destroy/{client}',						[UserController::class, 'destroy'])					->name('destroy');
	});
});

















Route::get('test', function (){
//	$order = \Modules\Shop\Models\Order::query()->findOrFail(1);
//	(new OrderActivationService())->activateOrder($order);
//	return (new DeactivateExceededTrafficOrders())->handle();
	(new \Modules\Server\Services\TrafficSyncService())->syncTraffic();
//	(new ClientProvisioningService())->provisionUser(\Modules\Shop\Models\Order::find(265));
});
