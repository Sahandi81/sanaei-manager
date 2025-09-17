<?php

namespace Modules\Server\Services;

use Modules\Logging\Traits\Loggable;
use Modules\Server\Models\Inbound;
use Modules\Server\Models\Server;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderConfig;
use Modules\Shop\Models\Product;

class SyncUserService
{
	use Loggable;

	protected array $panelInstances = [];

	/**
	 * @throws \Exception
	 */
	public static function createConfigsOnServer(Server $server, array $userDetails, array $inboundIds = []): void
	{
		$instance = new static();
		$instance->logInfo('syncConfigsOnServer', 'Starting to sync user configs on server', [
			'server_id' => $server->id,
			'user_id' => $userDetails['id']
		]);

		try {
			$panel = PanelFactory::make($server);
			if (count($inboundIds) < 1){
				$inboundIds = $server->activeInbounds->pluck('panel_inbound_id')->toArray();
			}

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
			$panel->createUser($userPayload, $userDetails['client_id'], $inboundIds);

			$instance->logInfo('syncConfigsOnServer', 'Successfully synced user configs on server', [
				'server_id' => $server->id,
				'user_id' => $userDetails['id']
			]);
		} catch (\Exception $e) {
			$instance->logError('syncConfigsOnServer', 'Failed to sync user configs on server', [
				'server_id' => $server->id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			throw $e;
		}
	}

	public function syncConfigsOnLocal(Order $order): bool
	{
		$this->logInfo('syncConfigsOnLocal', 'Starting to sync configs on local for order', [
			'order_id' => $order->id,
			'client_id' => $order->client_id
		]);

		try {
			$serverIds = $order->product?->servers?->pluck('id')->toArray();

			foreach ($this->getInboundsByServerIds($serverIds) as $key => $data) {
				if ($data['client']['id'] !== $order->uuid) {
					continue;
				}

				[$serverId, $inboundId] = explode('-', $key);

				$config = OrderConfig::query()->firstOrNew([
					'order_id' => $order->id,
					'server_id' => $serverId,
					'inbound_id' => $inboundId,
				]);

				if (!$config->exists) {
					$panel = $this->panelInstances[$serverId];
					$configData = [
						'client_id' => $order->client_id,
						'used_traffic_gb' => 0,
						'panel_email' => $data['client']['email'],
						'config' => stripslashes(
							$panel->generateConfig(
								$data['inbound'],
								$data['client'],
								$data['streamSettings']
							)
						),
					];

					$config->fill($configData)->save();

					$this->logInfo('createOrderConfig', 'Created new order config', [
						'order_id' => $order->id,
						'server_id' => $serverId,
						'inbound_id' => $inboundId
					]);
				} else {
					$this->logDebug('checkOrderConfig', 'Order config already exists', [
						'order_id' => $order->id,
						'server_id' => $serverId,
						'inbound_id' => $inboundId
					]);
				}
			}

			$this->logInfo('syncConfigsOnLocal', 'Successfully synced configs on local for order', [
				'order_id' => $order->id
			]);
			return true;
		} catch (\Exception $e) {
			$this->logError('syncConfigsOnLocal', 'Failed to sync configs on local for order', [
				'order_id' => $order->id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			return false;
		}
	}

	public function getInboundsByServerIds(array $serverIds): \Generator
	{
		$this->logDebug('getInboundsByServerIds', 'Fetching inbounds for server IDs', ['server_ids' => $serverIds]);

		$servers = Server::query()
			->whereIn('id', $serverIds)
			->get();

		foreach ($servers as $server) {
			if (!isset($this->panelInstances[$server->id])) {
				$this->panelInstances[$server->id] = PanelFactory::make($server);
				$this->logDebug('createPanelInstance', 'Created new panel instance', ['server_id' => $server->id]);
			}
			$panel = $this->panelInstances[$server->id];
			$inbounds = $panel->getInbounds() ?? [];

			if ($inbounds === false){
				continue;
			}

			foreach ($inbounds  as $inbound) {
				$settings = json_decode($inbound['settings'] ?? '{}', true);
				$streamSettings = json_decode($inbound['streamSettings'] ?? '{}', true);

				foreach ($settings['clients'] ?? [] as $client) {

					yield "{$server->id}-{$inbound['id']}" => [
						'server_id' => $server->id,
						'inbound' => $inbound,
						'client' => $client,
						'streamSettings' => $streamSettings,
					];
				}
			}
		}
	}

	public function syncUsersByProduct(Product $product): bool
	{
		$this->logInfo('syncUsersByProduct', 'Starting to sync users for product', [
			'product_id' => $product->id
		]);

		try {
			$query = Order::where('product_id', $product->id)
				->where('status', Order::STATUS_ACTIVE)
				->with(['client', 'product.servers.activeInbounds']);

			$query->chunk(200, function ($orders) use ($product) {
				foreach ($orders as $order) {
					$this->processOrderForNewServers($order, $product);
				}
			});

			$this->logInfo('syncUsersByProduct', 'Successfully synced users for product', [
				'product_id' => $product->id
			]);
			return true;
		} catch (\Exception $e) {
			$this->logError('syncUsersByProduct', 'Failed to sync users for product', [
				'product_id' => $product->id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			return false;
		}
	}

	public function processOrderForNewServers(Order $order, Product $product)
	{
		$currentServerIds = $product->servers->pluck('id')->all();

		$existingServerIds   = OrderConfig::where('order_id', $order->id)
			->pluck('server_id')
			->all();

		$clientServerCounts = array_count_values($existingServerIds); // [server_id => count]

		$productServerCounts = Inbound::query()
			->whereIn('server_id', $currentServerIds)
			->where('status', 1)
			->selectRaw('server_id, COUNT(*) as cnt')
			->groupBy('server_id')
			->pluck('cnt', 'server_id')
			->toArray();

		$missingServers = [];
		foreach ($currentServerIds as $serverId) {
			$have   = $clientServerCounts[$serverId]  ?? 0;
			$target = $productServerCounts[$serverId] ?? 0;
			$need   = max(0, $target - $have);

			if ($need > 0) {
				$missingServers[$serverId] = $need; // [server_id => need_count]
			}
		}


//		if ($order->id ==254){
//			dd([
//				'clientServerCounts'  => $clientServerCounts,   // موجودی فعلی
//				'productServerCounts' => $productServerCounts,  // ظرفیت هدف (inboundهای فعال)
//				'$missingServers'     => $missingServers,      // چندتا جدید روی هر سرور
//			]);
//		}

		if (empty($missingServers)) {
			return;
		}

		$userDetails = [
			'client_id' 	=> $order->client_id,
			'id' 			=> $order->uuid,
			'email' 		=> $order->client->name,
			'flow' 			=> '',
			'totalGB' 		=> byteToGigabyte($order->traffic_gb),
			'expiryTime' 	=> strtotime($order->expires_at) * 1000,
			'enable' 		=> true,
			'tgId' 			=> $order->client->telegram_id ?? '',
			'subId' 		=> $order->subs
		];

		foreach ($missingServers as $serverId => $missingCount) {
			$server = $product->servers->firstWhere('id', $serverId);

			if (!$server) {
				$this->logError('syncUserServerNotExists', 'A non exist server found in products', [
					'order_id' => $order->id,
					'server_id' => $serverId,
				]);
				continue;
			}

			if (!isset($this->panelInstances[$serverId])) {
				$this->panelInstances[$serverId] = PanelFactory::make($server);
			}
			$panel = $this->panelInstances[$serverId];
			$inbounds = $panel->getInbounds() ?? [];
			if ($inbounds === false){
				$this->logError('syncUserServerInboundNotExists', 'non exist inbound server found in products', [
					'order_id' => $order->id,
					'server_id' => $serverId,
				]);
				continue;
			}

			$clientExists = [];
			$allInbounds = [];

			foreach ($inbounds as $inbound) {
				$allInbounds[] = $inbound['id'];
				$settings = json_decode($inbound['settings'] ?? '{}', true);
				$streamSettings = json_decode($inbound['streamSettings'] ?? '{}', true);

				foreach ($settings['clients'] ?? [] as $client) {
					if ($client['id'] === $order->uuid) {
						$clientExists[$inbound['id']] = true;
					}
					if ($client['id'] === $order->uuid) {
						$config = OrderConfig::firstOrNew([
							'order_id' => $order->id,
							'server_id' => $serverId,
							'inbound_id' => $inbound['id'],
						]);
						if (!$config->exists) {
							$configData = [
								'client_id' => $order->client_id,
								'used_traffic_gb' => 0,
								'panel_email' => $client['email'],
								'config' => stripslashes(
									$panel->generateConfig(
										$inbound,
										$client,
										$streamSettings
									)
								),
							];
							$config->fill($configData)->save();

							$this->logInfo('createOrderConfig', 'Created new order config', [
								'order_id' => $order->id,
								'server_id' => $serverId,
								'inbound_id' => $inbound['id']
							]);
						}
					}
				}

			}
			$clientsNotExists = array_diff($allInbounds, array_keys($clientExists));
			// IF client doesn't exists, create it
			if (count($clientsNotExists) > 0) {
				try {
					SyncUserService::createConfigsOnServer($server, $userDetails, $clientsNotExists);

					$this->createOrderConfigsForServer($order, $server, $panel);

				} catch (\Exception $e) {
					$this->logError('processOrderForNewServers', 'Failed to create user on server', [
						'order_id'  => $order->id,
						'server_id' => $serverId,
						'error'     => $e->getMessage()
					]);
				}
			}
		}
	}

	private function createOrderConfigsForServer(Order $order, Server $server, $panel): void
	{
		$inbounds = $panel->getInbounds() ?? [];
		if (!$inbounds) {
			$this->logWarning('createOrderConfigsForServer', 'Panel returned false for inbounds after user creation', [
				'order_id'  => $order->id,
				'server_id' => $server->id,
			]);
			return;
		}

		foreach ($inbounds as $inbound) {
			$settings        = json_decode($inbound['settings'] ?? '{}', true);
			$streamSettings  = json_decode($inbound['streamSettings'] ?? '{}', true);

			foreach ($settings['clients'] ?? [] as $client) {
				if ($client['id'] !== $order->uuid) {
					continue;
				}

				$config = OrderConfig::firstOrNew([
					'order_id'  => $order->id,
					'server_id' => $server->id,
					'inbound_id'=> $inbound['id'],
				]);

				if (!$config->exists) {
					$configData = [
						'client_id'        => $order->client_id,
						'used_traffic_gb'  => 0,
						'panel_email'      => $client['email'] ?? $order->client->name,
						'config'           => stripslashes(
							$panel->generateConfig(
								$inbound,
								$client,
								$streamSettings
							)
						),
					];

					$config->fill($configData)->save();

					$this->logInfo('createOrderConfigAfterCreation', 'Created order config right after user creation', [
						'order_id'  => $order->id,
						'server_id' => $server->id,
						'inbound_id'=> $inbound['id'],
					]);
				} else {
					$this->logDebug('orderConfigExistsAfterCreation', 'Order config already exists after user creation', [
						'order_id'  => $order->id,
						'server_id' => $server->id,
						'inbound_id'=> $inbound['id'],
					]);
				}
			}
		}
	}
}
