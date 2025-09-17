<?php


use Illuminate\Console\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Translation\Translator;
use Modules\Logging\Services\LoggingService;

if (!function_exists('app_log')) {
	/**
	 * Log a message using the application logging service
	 *
	 * @param string $module
	 * @param string $action
	 * @param string $message
	 * @param array $context
	 * @param string $level
	 * @return void
	 */
	function app_log(
		string $module,
		string $action,
		string $message,
		array $context = [],
		string $level = 'info'
	): void {
		app(LoggingService::class)->log($module, $action, $message, $context, $level);
	}
}

if (!function_exists('app_log_error')) {
	/**
	 * Log an error message
	 *
	 * @param string $module
	 * @param string $action
	 * @param string $message
	 * @param array $context
	 * @return void
	 */
	function app_log_error(
		string $module,
		string $action,
		string $message,
		array $context = []
	): void {
		app(LoggingService::class)->logError($module, $action, $message, $context);
	}
}
function tr_helper(string $lang, ?string $key, ?string $attr = null): \Illuminate\Foundation\Application|array|string|Translator|Application|null
{
    $msg = $lang.'.'.$key;
    $str = trans($msg, [], Config::get('app.locale'));
    if (str_contains($str, ($msg))){
        $str = $key ?? 'UNDEFINED';
    }
    if (!is_null($attr)){
        $replacedText = tr_helper('validation', 'attributes.' . $attr);
        if (str_contains($replacedText, $attr)){
            $str = str_replace('?attr',  $attr, $str);
        }else{
            $str = str_replace('?attr', $replacedText, $str);
        }
    }
    return $str;
}

if (!function_exists('formatBytes')) {
	function formatBytes($bytes, $precision = 2): string
	{
		$units = ['B', 'KB', 'MB', 'GB', 'TB'];
		$bytes = max($bytes, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($units) - 1);
		return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
	}
}

function byteToGigabyte(int $bytes): float|int
{
	return $bytes * 1024 * 1024 * 1024;
}

function findNodePath(): string {
	$nodePath = trim(shell_exec('which node'));
	return $nodePath ?: 'node';
}

/**
 * Escape MarkdownV2 specials outside of `code` spans.
 * Specials: _ * [ ] ( ) ~ ` > # + - = | { } . !
 */
function escapeMarkdownV2PreserveCode(string $text): string
{
	$parts = preg_split('/(`[^`]*`)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
	if ($parts === false) {
		return $text;
	}

	$escaped = '';
	foreach ($parts as $part) {
		if ($part === '') {
			continue;
		}

		if ($part[0] === '`') {
			// keep code segment as-is
			$escaped .= $part;
		} else {
			// escape specials in normal text
			$escaped .= preg_replace(
				'/([\\\\_\*\[\]\(\)~`>#+\-=|{}\.!\/])/u',
				'\\\\$1',
				$part
			);
		}
	}

	return $escaped;
}
