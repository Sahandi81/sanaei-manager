<?php

namespace Modules\Shop\Services;

use Modules\QrGenerator\Services\QrGeneratorService;
use Modules\Shop\Models\Order;

class OrderQrService
{
	public function ensure(Order $order): string
	{
		if (!empty($order->qr_path)) {
			return $order->qr_path; // relative like: qr-codes/7/207/xxx.png
		}

		$url  = route('shop.orders.subs', $order->subs);
		$logo = base_path('/public/logo.png');

		// returns relative path e.g. 'qr-codes/7/207/oqeJHYZa91ta.png'
		$relative = QrGeneratorService::generateQr(
			$url,
			$order->client_id,
			$order->id,
			$order->subs,
			$logo
		);

		$order->update(['qr_path' => $relative]);
		return $relative;
	}

	public function publicUrl(string $relative): string
	{
		return url('storage/' . ltrim($relative, '/'));
	}

	public function absolutePath(string $relative): string
	{
		return storage_path('app/public/' . ltrim($relative, '/'));
	}
}
