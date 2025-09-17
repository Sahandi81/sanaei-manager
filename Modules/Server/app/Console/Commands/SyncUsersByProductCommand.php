<?php

namespace Modules\Server\Console\Commands;

use Illuminate\Console\Command;
use Modules\Shop\Models\Product;
use Modules\Server\Services\SyncUserService;

class SyncUsersByProductCommand extends Command
{
	protected $signature = 'servers:sync-users';

	protected $description = 'Sync users and their configs on all servers for all active products';

	public function handle(SyncUserService $service): int
	{
		$products = Product::getAllActiveProducts();

		foreach ($products as $product) {
			if (+$product->is_test) {
				continue;
			}

			$this->info("Starting user sync for product {$product->id} ...");

			$start = microtime(true);

			$res = $service->syncUsersByProduct($product);

			$elapsed = round(microtime(true) - $start, 2);

			if ($res) {
				$this->info("✅ User sync completed successfully for product {$product->id} in {$elapsed} seconds.");
			} else {
				$this->error("❌ User sync failed for product {$product->id} in {$elapsed} seconds. Check logs.");
			}
		}

		$this->line("All jobs finished ⏱️");

		return self::SUCCESS;
	}
}
