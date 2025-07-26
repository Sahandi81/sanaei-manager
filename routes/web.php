<?php

use App\Http\Controllers\Auth\LoginCover;
use App\Http\Controllers\Auth\RegisterCover;
use App\Http\Controllers\Auth\ResetPasswordCover;
use App\Http\Controllers\Auth\TwoStepsCover;
use Illuminate\Support\Facades\Route;

Route::get('login', 											[LoginCover::class, 'index'])						->name('login');
Route::post('login', 											[LoginCover::class, 'index'])						->name('login');
Route::get('register',	 										[RegisterCover::class, 'index'])					->name('register');
Route::get('reset-password', 									[ResetPasswordCover::class, 'index'])				->name('reset_password');


Route::group(['prefix' => 'panel', 'as' => 'panel.', 'middleware' => 'auth:sanctum'], function (){
	Route::get('/',					);
});
