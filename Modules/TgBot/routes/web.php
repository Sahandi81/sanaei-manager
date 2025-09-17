<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\TgBot\Http\Controllers\BroadcastController;
use Modules\TgBot\Http\Controllers\TgBotController;
use Modules\TgBot\Http\Controllers\WebhookController;
use Modules\TgBot\Http\Middleware\LogTelegramWebhook;

Route::any('/telegram/webhook/{webhook}', [WebhookController::class, 'handle'])
	->middleware(LogTelegramWebhook::class)
	->withoutMiddleware(VerifyCsrfToken::class);;


Route::group(['prefix' => '/tgbot', 'as' => 'tgbot.', 'middleware' => 'auth:sanctum'], function (){
	Route::get('/broadcasts/create', 					[BroadcastController::class, 'create'])						->name('broadcasts.create');
	Route::post('/broadcasts', 							[BroadcastController::class, 'store'])						->name('broadcasts.store');

});
