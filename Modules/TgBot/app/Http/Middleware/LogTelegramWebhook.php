<?php

namespace Modules\TgBot\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogTelegramWebhook
{
	public function handle(Request $request, Closure $next)
	{
		$t0 = microtime(true);

		$raw = $request->getContent();
		try {
			$json = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
			$prettyJson = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		} catch (\Throwable $e) {
			$json = $request->all();
			$prettyJson = json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}

		Log::channel('telegram')->info('TG webhook IN', [
			'json'    => $prettyJson,
		]);

		$response = $next($request);

//		$ms = (int) ((microtime(true) - $t0) * 1000);
//		$body = method_exists($response, 'getContent') ? $response->getContent() : null;
//
//		// Pretty-print response body if it's JSON
//		if ($body && json_decode($body)) {
//			$body = json_encode(json_decode($body), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//		}
//
//		Log::channel('telegram')->info('TG webhook OUT', [
//			'status' => $response->getStatusCode(),
//			'ms'     => $ms,
//			'body'   => $body,
//		]);

		return $response;
	}
}
