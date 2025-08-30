<?php

namespace Modules\Server\Console\Commands;

use Illuminate\Console\Command;
use Modules\Server\Services\TrafficSyncService;

class SyncTrafficCommand extends Command
{
	protected $signature = 'servers:sync-traffic';
	protected $description = 'Sync traffic usage from servers';

	public function handle(TrafficSyncService $service): int
	{
		$service->syncTraffic();
		$this->info('Traffic sync triggered.');
		return self::SUCCESS;
	}
}
