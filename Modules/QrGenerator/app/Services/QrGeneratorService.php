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
		$outputPath = Storage::disk('public')->path($relativePath);

		Storage::disk('public')->makeDirectory("qr-codes/{$clientId}/{$productId}");

		// Dynamically get node path
		$nodePath = findNodePath();

		$process = new Process([
			$nodePath,
			self::getNodeScriptPath(),
			$url,
			$outputPath,
			$logoPath
		]);
		$process->setTimeout(60);
		$process->run();


		if (!$process->isSuccessful()) {
			$res = $process->getExitCodeText();
			throw new \RuntimeException("QR Code generation failed: " . $process->getErrorOutput() . '-' . $res);
		}

		return $relativePath;
	}
}
