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
