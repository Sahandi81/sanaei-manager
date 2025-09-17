<?php

namespace Modules\TgBot\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\Client\Models\Client;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\Product;

class BroadcastService
{
	public function __construct(protected TelegramApiService $tg) {}

	/**
	 * @param array{only?:'active'|'all'|'testless_active'|'testless_all'} $filters
	 */
	public function sendToOwner(
		User $owner,
		string $text,
		array $filters = [],
		?array $replyMarkup = null,
		?string $parseMode = 'MarkdownV2'
	): array {
		$only = $filters['only'] ?? 'active';

		$query = Client::query()
			->where('user_id', $owner->id)
			->where('type', 'telegram')
			->whereNotNull('telegram_id');

		// حالت‌های active
		if (in_array($only, ['active', 'testless_active'], true)) {
			$query->where('status', 1);
		}

		// حالت‌های testless: کاربران بدون هیچ سفارشی از نوع تست
		if (in_array($only, ['testless_active', 'testless_all'], true)) {
			$clientTable  = (new Client())->getTable();   // مثلا clients
			$orderTable   = (new Order())->getTable();    // مثلا orders
			$productTable = (new Product())->getTable();  // مثلا products

			$query->whereNotExists(function ($q) use ($clientTable, $orderTable, $productTable) {
				$q->select(DB::raw(1))
					->from("$orderTable as o")
					->join("$productTable as p", 'p.id', '=', 'o.product_id')
					->whereColumn('o.client_id', "$clientTable.id")
					->where('p.is_test', 1);
			});
		}

		$total  = (clone $query)->count();
		$sent   = 0;
		$failed = 0;

		if ($parseMode === 'MarkdownV2') {
			$text = escapeMarkdownV2PreserveCode($text);
		}

		$botToken = $owner->telegram_bot_token;

		$query->select('id', 'telegram_id')->chunkById(1000, function ($clients) use (
			$botToken, $text, $replyMarkup, $parseMode, $owner, &$sent, &$failed
		) {
			foreach ($clients as $client) {
				try {
					$this->tg->sendMessage($botToken, $client->telegram_id, $text, $replyMarkup, $parseMode);
					$sent++;
					usleep(40000); // ~25 msg/sec
				} catch (\Throwable $e) {
					$failed++;
					$msg = $e->getMessage() ?? 'unknown';
					Log::warning('tgbot.broadcast.fail', [
						'owner_id' => $owner->id,
						'client_id' => $client->id,
						'telegram_id' => $client->telegram_id,
						'error' => $msg,
					]);
					if (preg_match('/Too Many Requests: retry after (\\d+)/i', $msg, $m)) {
						sleep(max(1, (int) $m[1]));
					} elseif (str_contains($msg, 'Too Many Requests')) {
						sleep(1);
					}
				}
			}
		});

		return ['sent' => $sent, 'failed' => $failed, 'total' => $total];
	}
}
