<?php

namespace Modules\Server\Services;

use Modules\Logging\Traits\Loggable;
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
	public static function createConfigsOnServer(Server $server, array $userDetails): void
	{
		$instance = new static();
		$instance->logInfo('syncConfigsOnServer', 'Starting to sync user configs on server', [
			'server_id' => $server->id,
			'user_id' => $userDetails['id']
		]);

		try {
			$panel = PanelFactory::make($server);
			$inboundIds = $server->activeInbounds->pluck('panel_inbound_id')->toArray();

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

			foreach ($panel->getInbounds() ?? [] as $inbound) {
				$settings = json_decode($inbound['settings'] ?? '{}', true);
				$streamSettings = json_decode($inbound['streamSettings'] ?? '{}', true);

				foreach ($settings['clients'] ?? [] as $client) {
					$this->logDebug('yieldInboundData', 'Yielding inbound data', [
						'server_id' => $server->id,
						'inbound_id' => $inbound['id'],
						'client_id' => $client['id']
					]);

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

	protected function processOrderForNewServers(Order $order, Product $product)
	{
		$currentServerIds = $product->servers->pluck('id')->toArray();

		$existingServerIds = OrderConfig::where('order_id', $order->id)
			->pluck('server_id')
			->toArray();

		$newServerIds = array_diff($currentServerIds, $existingServerIds);

		if (empty($newServerIds)) {
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

		foreach ($newServerIds as $serverId) {
			$server = $product->servers->firstWhere('id', $serverId);
			if (!$server) {
				continue;
			}

			if (!isset($this->panelInstances[$serverId])) {
				$this->panelInstances[$serverId] = PanelFactory::make($server);
			}
			$panel = $this->panelInstances[$serverId];

			$inbounds = $panel->getInbounds() ?? [];

			$clientExists = false;

			foreach ($inbounds as $inbound) {
				$settings = json_decode($inbound['settings'] ?? '{}', true);
				$streamSettings = json_decode($inbound['streamSettings'] ?? '{}', true);

				foreach ($settings['clients'] ?? [] as $client) {
					if ($client['id'] === $order->uuid) {
						$clientExists = true;
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

			// اگر کلاینت وجود ندارد، بسازش
			if (!$clientExists) {
				try {
					SyncUserService::createConfigsOnServer($server, $userDetails);
				} catch (\Exception $e) {
					$this->logError('processOrderForNewServers', 'Failed to create user on server', [
						'order_id' => $order->id,
						'server_id' => $serverId,
						'error' => $e->getMessage()
					]);
				}
			}
		}
	}
	public function syncConfigsOnLocalForServers(Product $product, array $serverIds): bool
	{
		try {
			$orders = Order::where('product_id', $product->id)
				->where('status', Order::STATUS_ACTIVE)
				->with(['client'])
				->get();

			foreach ($serverIds as $serverId) {
				$server = $product->servers->firstWhere('id', $serverId);
				if (!$server) {
					continue;
				}

				if (!isset($this->panelInstances[$serverId])) {
					$this->panelInstances[$serverId] = PanelFactory::make($server);
				}
				$panel = $this->panelInstances[$serverId];

				$inbounds = $panel->getInbounds() ?? [];

				foreach ($inbounds as $inbound) {
					echo '10-';
					$settings = json_decode($inbound['settings'] ?? '{}', true);
					$streamSettings = json_decode($inbound['streamSettings'] ?? '{}', true);

					foreach ($settings['clients'] ?? [] as $client) {
						$order = $orders->firstWhere('uuid', $client['id']);
						if (!$order) {
							continue;
						}
						// Create or update config
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

			$this->logInfo('syncConfigsOnLocalForServers', 'Successfully synced configs for servers', [
				'product_id' => $product->id,
				'server_ids' => $serverIds
			]);
			return true;
		} catch (\Exception $e) {
			$this->logError('syncConfigsOnLocalForServers', 'Failed to sync configs for servers', [
				'product_id' => $product->id,
				'error' => $e->getMessage(),
				'trace' => $e->getTraceAsString()
			]);
			return false;
		}
	}
}
