<?php

namespace Modules\Shop\Services;

use Illuminate\Support\Facades\DB;
use Modules\Logging\Traits\Loggable;
use Modules\Server\Models\Server;
use Modules\Server\Services\PanelFactory;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderConfig;

class OrderConfigDeactivationService
{
	use Loggable;

	public function disableAllConfigsForOrder(Order $order): int
	{
		$disabledCount = 0;
		$error = false;

		$configs = $order->configs()
			->get();

		foreach ($configs as $cfg) {
			try {
				$server = $cfg->server ?? Server::find($cfg->server_id);
				if (!$server) {
					$this->logError('orderConfigDelete', 'Server not found', [
						'order_id'     => $order->id,
						'order_config' => $cfg->id,
						'server_id'    => $cfg->server_id,
					]);
					continue;
				}

				if (empty($cfg->client_id)) {
					$this->logError('delClient', 'Missing client_id on OrderConfig', [
						'order_id'     => $order->id,
						'order_config' => $cfg->id,
						'server_id'    => $server->id,
						'inbound_id'   => $cfg->inbound_id,
					]);
					continue;
				}

				$panel = PanelFactory::make($server);
				$ok = $panel->deleteClientByUuid((int) $cfg->inbound_id, (string) $order->uuid);

				if ($ok) {
					$this->logInfo('delClient', 'Client deleted remotely', [
						'order_id'     => $order->id,
						'order_config' => $cfg->id,
						'server_id'    => $server->id,
						'inbound_id'   => $cfg->inbound_id,
						'uuid'         => $cfg->client_id,
					]);
				} else {
					$error = true;
					$this->logError('delClient', 'Remote delete returned false', [
						'order_id'     => $order->id,
						'order_config' => $cfg->id,
						'server_id'    => $server->id,
						'inbound_id'   => $cfg->inbound_id,
						'uuid'         => $cfg->client_id,
					]);
				}
			} catch (\Throwable $e) {
				$error = true;
				$this->logError('delClient', 'Remote delete exception', [
					'order_id'     => $order->id,
					'order_config' => $cfg->id,
					'server_id'    => $cfg->server_id,
					'inbound_id'   => $cfg->inbound_id,
					'uuid'         => $cfg->client_id,
					'error'        => $e->getMessage(),
				]);
			}
		}
		$order->update(['attempt_to_remove' => $order->attempt_to_remove + 1]);
		if (!$error){
			DB::transaction(function () use ($order, $configs, &$disabledCount) {
				$configIds = $configs->pluck('id')->all();

				if (!empty($configIds)) {
					$disabledCount = OrderConfig::whereIn('id', $configIds)->delete();
				}

				$order->update([
					'status'          => Order::STATUS_EXPIRED,
					'disabled_reason' => 'TRAFFIC_EXCEEDED',
				]);
			});
		}

		return (int) $disabledCount;
	}
}
