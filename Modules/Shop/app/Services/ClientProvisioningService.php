<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\DB;
use Modules\Client\Models\Client;
use Modules\Server\Models\Server;
use Modules\Server\Services\PanelFactory;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderConfig;
use Modules\Logging\Traits\Loggable;

	class ClientProvisioningService
{
	use Loggable;

	public function provisionUser(Order $order): void
	{
		$product = $order->product;
		$client = $order->client;

		$this->logInfo('provision_start', 'Starting user provisioning', [
			'order_id' => $order->id,
			'client_id' => $client->id,
			'product_id' => $product->id,
		]);

		DB::transaction(function () use ($product, $client, $order) {
			$activeServers = $product->servers()
				->where('status', Server::ACTIVE_STATUS)
				->with('activeInbounds')
				->get();

			if ($activeServers->isEmpty()) {
				return;
			}

			foreach ($activeServers as $server) {
				$this->provisionOnServer($server, $client, $order);
			}
		});

		$this->logInfo('provision_complete', 'User provisioning completed', [
			'order_id' => $order->id,
			'client_id' => $client->id,
		]);
	}

	protected function provisionOnServer(Server $server, Client $client, Order $order): void
	{
		$panel = PanelFactory::make($server);

		$this->logDebug('server_provision_start', 'Starting server provisioning', [
			'server_id' => $server->id,
			'inbound_count' => $server->activeInbounds->count(),
		]);

		foreach ($server->activeInbounds as $inbound) {
			try {
				$this->logDebug('inbound_provision_start', 'Creating user on inbound', [
					'server_id' => $server->id,
					'inbound_id' => $inbound->id,
				]);
				$userPayload = [
					'id' => $userDetails['id'],
					'email' => $userDetails['email'],
					'flow' => $userDetails['flow'],
					'totalGB' => $userDetails['totalGB'],
					'expiryTime' => $userDetails['expiryTime'],
					'enable' => $userDetails['enable'],
					'tgId' => $userDetails['tgId'],
					'subId' => $userDetails['subId'],
				];
				$response = $panel->createUser($client);

				$orderConfig = OrderConfig::updateOrCreate(
					[
						'server_id' => $server->id,
						'inbound_id' => $inbound->id,
						'order_id' => $order->id,
					],
					[
						'client_id' => $client->id,
						'config' => $response['config'],
						'expires_at' => $order->expires_at,
					]
				);

				$this->logInfo('user_created', 'User successfully provisioned', [
					'server_id' => $server->id,
					'inbound_id' => $inbound->id,
					'order_config_id' => $orderConfig->id,
				]);

			} catch (\Exception $e) {
				$this->logError('provision_failed', 'Failed to provision user', [
					'server_id' => $server->id,
					'inbound_id' => $inbound->id,
					'error' => $e->getMessage(),
					'trace' => $e->getTraceAsString(),
				]);

				continue;
			}
		}
	}
}
