<?php

use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Modules\Server\Http\Controllers\ServerController;

Route::middleware([
	EnsureFrontendRequestsAreStateful::class,
	'auth:sanctum'
])->prefix('v1/servers')->as('v1.servers.')->group(function () {
	Route::post('/{server}/test-connection', 				[ServerController::class, 'testConnection'])			->name('test_connection');
	Route::post('/{server}/sync-inbounds', 					[ServerController::class, 'syncInbounds'])				->name('sync_inbounds');
});
