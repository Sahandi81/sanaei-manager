<?php

use Illuminate\Support\Facades\Route;
use Modules\Permission\App\Http\Controllers\Admin\PermissionController as AdminPermissionController;
use Modules\Permission\App\Http\Controllers\Admin\RoleController as AdminRoleController;
use Modules\Permission\App\Http\Controllers\PermissionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => '/admin/panel/roles', 'as' => 'admin.panel.roles.', 'middleware' => ['auth:sanctum']], function (){

	Route::get('/',								[AdminRoleController::class, 'index'])								->name('index');
	Route::get('/create',						[AdminRoleController::class, 'create'])								->name('create');
	Route::post('/store',						[AdminRoleController::class, 'store'])								->name('store');
	Route::get('/edit/{role}',					[AdminRoleController::class, 'edit'])								->name('edit');
	Route::post('/update/{role}',				[AdminRoleController::class, 'update'])								->name('update');

	Route::group(['prefix' => '/permissions', 'as' => 'permissions.'], function (){
		Route::get('/sync/routes',				[PermissionController::class, 'sync'])								->name('sync.routes');
		Route::post('/update/{role}',			[AdminPermissionController::class, 'syncPermissions'])				->name('sync.permission');
	});

});
