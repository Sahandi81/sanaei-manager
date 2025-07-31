<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Modules\Logging\Services\LoggingService;

abstract class Controller
{
	protected LoggingService $logger;

	public function __construct()
	{
		$this->logger = app(LoggingService::class);
	}

	protected function log(string $action, string $message, int $serverId, array $extra = []): void
	{
		$this->logger->logInfo('Server', $action, $message, array_merge([
			'server_id' => $serverId,
			'user_id' => Auth::id(),
		], $extra));
	}
}
