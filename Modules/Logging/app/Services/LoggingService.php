<?php

namespace Modules\Logging\Services;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Logging\Models\Logging;

class LoggingService
{
	public function log(
		string $module,
		string $action,
		string $message,
		array $context = [],
		string $level = 'info'
	): void {
		try {
			Logging::query()->create([
				'module' => $module,
				'action' => $action,
				'message' => $message,
				'context' => !empty($context) ? json_encode($context) : null,
				'level' => $level,
				'user_id' => Auth::id(),
			]);

		} catch (Exception $e) {
			// Fallback to default Laravel log if database logging fails
			Log::error("Failed to save log entry: " . $e->getMessage(), [
				'original_log' => [
					'module' => $module,
					'action' => $action,
					'message' => $message,
					'context' => $context,
					'level' => $level,
				]
			]);
		}
	}

	/**
	 * Logging an error
	 *
	 * @param string $module
	 * @param string $action
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function logError(
		string $module,
		string $action,
		string $message,
		array $context = []
	): void {
		$this->log($module, $action, $message, $context, 'error');
	}

	/**
	 * Logging a warning
	 *
	 * @param string $module
	 * @param string $action
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function logWarning(
		string $module,
		string $action,
		string $message,
		array $context = []
	): void {
		$this->log($module, $action, $message, $context, 'warning');
	}

	/**
	 * Logging an info message
	 *
	 * @param string $module
	 * @param string $action
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	public function logInfo(
		string $module,
		string $action,
		string $message,
		array $context = []
	): void {
		$this->log($module, $action, $message, $context, 'info');
	}
}
