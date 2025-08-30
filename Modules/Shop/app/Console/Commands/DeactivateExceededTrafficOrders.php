<?php


namespace Modules\Shop\Console\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\OrderConfigDeactivationService;


class DeactivateExceededTrafficOrders extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * --dry-run : فقط گزارش بده، تغییری اعمال نکن
	 * --chunk=500 : اندازه‌ی هر دسته در پردازش chunk
	 */
	protected $signature = 'orders:deactivate-exceeded-traffic {--dry-run} {--chunk=500}';


	/** @var string */
	protected $description = 'غیرفعال‌سازی کانفیگ‌های سفارش‌هایی که مصرف ترافیک‌شان از حد مجاز عبور کرده است';


	public function handle(OrderConfigDeactivationService $service): int
	{
		$this->info('Starting: orders:deactivate-exceeded-traffic');


		$chunk = max((int) $this->option('chunk'), 100);
		$dryRun = (bool) $this->option('dry-run');
		$totalOrders = 0;
		$totalConfigs = 0;


		$baseQuery = Order::query()
			->where('status', 1)
			->where('attempt_to_remove', '<', 3)
			->whereNotNull('used_traffic_gb')
			->whereNotNull('traffic_gb')
			->whereColumn('used_traffic_gb', '>', 'traffic_gb');

//		var_export($baseQuery->get());die();

		$baseQuery->select(['id', 'uuid'])
			->orderBy('id')
			->chunkById($chunk, function ($orders) use ($service, $dryRun, &$totalOrders, &$totalConfigs) {
				foreach ($orders as $order) {
					$totalOrders++;


					if ($dryRun) {
						$this->line("[DRY] Order #{$order->id} would be processed");
						continue;
					}


					try {
						$disabled = $service->disableAllConfigsForOrder($order);
						$totalConfigs += $disabled;
						$this->line("Order #{$order->id}: disabled {$disabled} configs");
					} catch (\Throwable $e) {
						Log::error('Failed to deactivate configs for order', [
							'order_id' => $order->id,
							'error' => $e->getMessage(),
						]);
						$this->warn("Order #{$order->id}: ERROR - " . $e->getMessage());
					}
				}
			});


		$this->info("Done. Orders scanned: {$totalOrders}, Configs disabled: {$totalConfigs}");
		return self::SUCCESS;
	}
}
