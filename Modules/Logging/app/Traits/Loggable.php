<?php

namespace Modules\Logging\Traits;

use Illuminate\Support\Facades\Auth;
use Modules\Logging\Services\LoggingService;

trait Loggable
{
	protected function logInfo(string $action, string $message, array $context = [], string $module = ''): void
	{
		app(LoggingService::class)->log(
			$module ?: $this->getLogModuleName(),
			$action,
			$message,
			array_merge(['user_id' => Auth::id()], $context),
			'info'
		);
	}
	protected function logDebug(string $action, string $message, array $context = [], string $module = ''): void
	{
		app(LoggingService::class)->log(
			$module ?: $this->getLogModuleName(),
			$action,
			$message,
			array_merge(['user_id' => Auth::id()], $context),
			'debug'
		);
	}

	protected function logError(string $action, string $message, array $context = [], string $module = ''): void
	{
		app(LoggingService::class)->log(
			$module ?: $this->getLogModuleName(),
			$action,
			$message,
			array_merge(['user_id' => Auth::id()], $context),
			'error'
		);
	}

	protected function logWarning(string $action, string $message, array $context = [], string $module = ''): void
	{
		app(LoggingService::class)->log(
			$module ?: $this->getLogModuleName(),
			$action,
			$message,
			array_merge(['user_id' => Auth::id()], $context),
			'warning'
		);
	}

	private function getLogModuleName(): string
	{

		return (get_class($this));
	}
}
