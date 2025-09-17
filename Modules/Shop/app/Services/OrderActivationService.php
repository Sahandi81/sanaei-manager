<?php

namespace Modules\Shop\Services;

use Modules\Server\Services\PanelFactory;
use Modules\Server\Services\SyncUserService;
use Modules\Shop\Models\Order;

class OrderActivationService
{
	/**
	 * @throws \Exception
	 */
	public function activateOrder(Order $order): void
	{
		$order->update(['status' => Order::STATUS_ACTIVE]);

		$product 		= $order->product;
		$servers 		= $product->servers()->with('activeInbounds')->get();
		$userDetails 	= [
			'client_id' 	=> $order->client_id,
			'id' 			=> $order->uuid,
			'email' 		=> $order->client->name,
			'flow' 			=> '',
			'totalGB' 		=> byteToGigabyte($order->traffic_gb),
			'expiryTime' 	=> strtotime("+{$product->duration_days} days") * 1000,
			'enable' 		=> true,
			'tgId' 			=> $order->client->telegram_id ?? '',
			'subId' 		=> $order->subs
		];
		foreach ($servers as $server) {
			SyncUserService::createConfigsOnServer($server, $userDetails);
		}

		(new SyncUserService())->syncConfigsOnLocal($order);

//		$allActiveInbounds = $servers->flatMap->activeInbounds;

	}
}
