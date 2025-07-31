<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Server\Http\Controllers\InboundController;
use Modules\Server\Http\Controllers\ServerController;

Route::middleware([
	EnsureFrontendRequestsAreStateful::class,
	'auth:sanctum'
])->prefix('v1/servers/{server}')->as('v1.servers.')->group(function () {

	Route::post('/test-connection', 				[ServerController::class, 'testConnection'])					->name('test_connection');
	Route::post('/sync-inbounds', 					[ServerController::class, 'syncInbounds'])						->name('sync_inbounds');

	Route::group(['prefix' => '/inbounds', 'as' => 'inbounds.'], function (){

		Route::post('/{inbound}/toggle',			[InboundController::class, 'toggle'])							->name('toggle');

	});
});
