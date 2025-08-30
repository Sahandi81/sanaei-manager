<?php

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;
use Modules\TgBot\Http\Controllers\TgBotController;
use Modules\TgBot\Http\Controllers\WebhookController;
use Modules\TgBot\Http\Middleware\LogTelegramWebhook;

Route::any('/telegram/webhook/{webhook}', [WebhookController::class, 'handle'])
	->middleware(LogTelegramWebhook::class)
	->withoutMiddleware(VerifyCsrfToken::class);;
