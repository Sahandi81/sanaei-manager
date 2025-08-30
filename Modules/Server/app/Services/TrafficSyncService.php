<?php

namespace Modules\Server\Services;

use Illuminate\Support\Facades\DB;
use Modules\Logging\Traits\Loggable;
use Modules\Server\Models\Server;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderConfig;

class TrafficSyncService
{
	use Loggable;

	public function syncTraffic()
	{
		$servers = Server::all();

		foreach ($servers as $server) {
			try {
				$this->logInfo('syncTraffic', 'Starting traffic sync for server', [
					'server_id' => $server->id,
					'server_name' => $server->name
				]);

				$panel = PanelFactory::make($server);
				$inbounds = $panel->getInbounds();

				if (!$inbounds || !is_array($inbounds)) {
					$this->logWarning('syncTraffic', 'No inbound data received', [
						'server_id' => $server->id
					]);
					continue;
				}
				$usageMap = [];
				foreach ($inbounds as $inbound) {
					foreach ($inbound['clientStats'] ?? [] as $client) {
						$clientId = $client['email'] ?? null;
						if (!$clientId) continue;
						$usedGB = round(($client['up'] + $client['down']) / 1073741824, 2);
						$usageMap[$clientId] = $usedGB;
					}
				}
				if (empty($usageMap)) {
					$this->logWarning('syncTraffic', 'No client stats found in inbounds', [
						'server_id' => $server->id
					]);
					continue;
				}

				$configs = OrderConfig::query()
					->where('server_id', $server->id)
					->get(['id', 'order_id', 'client_id', 'used_traffic_gb', 'panel_email']);

				$updatesConfigs = [];
				$orderUsageDiff = [];


				foreach ($configs as $config) {
					if (!isset($usageMap[$config->panel_email])) continue;

					$newUsage = $usageMap[$config->panel_email];
					$oldUsage = $config->used_traffic_gb;
					$diff = $newUsage - $oldUsage;
					if ($diff <= 0) continue;

					$updatesConfigs[] = [
						'id' => $config->id,
						'used_traffic_gb' => $newUsage
					];

					if (!isset($orderUsageDiff[$config->order_id])) {
						$orderUsageDiff[$config->order_id] = 0;
					}
					$orderUsageDiff[$config->order_id] += $diff;

					$this->logInfo('syncTraffic', 'Config usage updated', [
						'server_id' => $server->id,
						'order_config_id' => $config->id,
						'old_usage_gb' => $oldUsage,
						'new_usage_gb' => $newUsage,
						'diff_gb' => $diff
					]);
				}
				if (!empty($updatesConfigs)) {
					$this->batchUpdate('order_configs', $updatesConfigs, 'id');
				}

				if (!empty($orderUsageDiff)) {
					foreach ($orderUsageDiff as $orderId => $diff) {
						Order::where('id', $orderId)->increment('used_traffic_gb', $diff);
						$this->logInfo('syncTraffic', 'Order usage incremented', [
							'order_id' => $orderId,
							'diff_gb' => $diff
						]);
					}
				}

			} catch (\Exception $e) {
				$this->logError('syncTraffic', 'Traffic sync failed', [
					'server_id' => $server->id,
					'error' => $e->getMessage()
				]);
			}
		}
	}

	private function batchUpdate(string $table, array $values, string $index)
	{
		if (empty($values)) return;

		$cases = [];
		$ids = [];
		$columns = array_keys($values[0]);

		foreach ($columns as $col) {
			if ($col === $index) continue;
			$cases[$col] = "CASE";
		}

		foreach ($values as $row) {
			$id = (int)$row[$index];
			$ids[] = $id;
			foreach ($columns as $col) {
				if ($col === $index) continue;
				$val = is_numeric($row[$col]) ? $row[$col] : "'{$row[$col]}'";
				$cases[$col] .= " WHEN {$index} = {$id} THEN {$val}";
			}
		}

		$updates = '';
		foreach ($cases as $col => $sql) {
			$updates .= "{$col} = {$sql} END, ";
		}

		$updates = rtrim($updates, ', ');
		$ids = implode(',', $ids);
		DB::update("UPDATE {$table} SET {$updates} WHERE {$index} IN ({$ids})");
	}
}
