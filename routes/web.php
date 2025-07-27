<?php

use App\Http\Controllers\Auth\LoginCover;
use App\Http\Controllers\Auth\RegisterCover;
use App\Http\Controllers\Auth\ResetPasswordCover;
use App\Http\Controllers\Auth\TwoStepsCover;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function (){
	return redirect()->route('panel.index');
});

Route::get('login', 											[LoginCover::class, 'index'])						->name('login');
Route::post('login', 											[AuthController::class, 'login'])					->name('post_login');
Route::get('register',	 										[RegisterCover::class, 'index'])					->name('register');
Route::get('reset-password', 									[ResetPasswordCover::class, 'index'])				->name('reset_password');


Route::group(['prefix' => 'panel', 'as' => 'panel.', 'middleware' => 'auth:sanctum'], function (){
	Route::get('/',												[PanelController::class, 'index'])					->name('index');
});
