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
			if (!$module) {
				$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
				$caller = class_basename($trace[1]['class'] ?? 'UnknownModule');
				$module = $caller;
			}

			Logging::query()->create([
				'module' => $module,
				'action' => $action,
				'message' => $message,
				'context' => !empty($context) ? json_encode($context) : null,
				'level' => $level,
				'user_id' => Auth::id(),
			]);

		} catch (Exception $e) {
			Log::error("Failed to save log entry: " . $e->getMessage(), [
				'original_log' => compact('module', 'action', 'message', 'context', 'level'),
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
