<?php

namespace Modules\QrGenerator\Services;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class QrGeneratorService
{
	public static function getNodeScriptPath(): string
	{
		return base_path('Modules/QrGenerator/packages/qr-code-styling/generate-qr.js');
	}

	public static function generateQr(string $url, int $clientId, int $productId, string $subs, string $logoPath): string
	{
		$relativePath = "qr-codes/{$clientId}/{$productId}/{$subs}.png";
		$outputPath   = Storage::disk('public')->path($relativePath);

		// اطمینان از وجود پوشهٔ خروجی
		Storage::disk('public')->makeDirectory("qr-codes/{$clientId}/{$productId}");

		// مسیر Node (تابع کمکی خودت)
		$nodePath   = findNodePath();
		$scriptPath = self::getNodeScriptPath();

		// مسیر Chrome باندل‌شدهٔ puppeteer (همانی که پیدا کردی)
		$chromePath = '/var/cache/puppeteer/chrome/linux-139.0.7258.66/chrome-linux64/chrome';

		// ENVهای حیاتی برای php-fpm/www-data
		$env = [
			'PUPPETEER_EXECUTABLE_PATH' => $chromePath,
			'PUPPETEER_CACHE_DIR'       => '/var/cache/puppeteer',
			'PPTR_RUNTIME_DIR'          => '/var/cache/puppeteer',   // در JS خوانده می‌شود
			'HOME'                      => '/var/cache/puppeteer',
			'XDG_RUNTIME_DIR'           => '/var/cache/puppeteer/run',
			'TMPDIR'                    => '/var/cache/puppeteer/tmp',
		];

		$process = new Process([$nodePath, $scriptPath, $url, $outputPath, $logoPath], null, $env);
		$process->setTimeout(60);
		$process->run();

		if (!$process->isSuccessful()) {
			throw new \RuntimeException(
				"QR Code generation failed: " . $process->getErrorOutput() . '-' . $process->getExitCodeText()
			);
		}

		return $relativePath;
	}
}
