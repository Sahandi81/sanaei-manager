<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Modules\Client\Models\Client;
use Modules\Finance\Models\Wallet;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\InlineKeyboardService;
use Modules\TgBot\Services\TelegramApiService;

class WalletTopupHandler implements Handler
{
	public function __construct(
		protected TelegramApiService $tg,
		protected BotMessageService $msg,
		protected InlineKeyboardService $ikb
	) {}

	public function handle(User $owner, array $update): void
	{
		$cb  = $update['callback_query'] ?? [];
		$m   = $cb['message'] ?? [];
		$chatId    = $m['chat']['id'] ?? null;
		$messageId = $m['message_id'] ?? null;
		if (!$chatId || !$messageId) return;

		$from = $cb['from'] ?? [];
		$telegramId = (string)($from['id'] ?? '');
		if ($telegramId === '') return;

		// پیدا کردن کلاینت فعلی این صاحب ربات
		$client = Client::query()
			->where('user_id', $owner->id)
			->where('telegram_id', $telegramId)
			->first();

		if (!$client) return;

		// کیف پول کاربر (IRT = تومان). اگر نبود بساز.
		$wallet = Wallet::query()->firstOrCreate(
			[
				'owner_type' => Client::class,
				'owner_id'   => $client->id,
				'currency'   => 'IRT',
			],
			[
				'balance_minor' => 0,
				'status'        => Wallet::STATUS_ACTIVE,
				'meta'          => null,
			]
		);

		$balanceFmt = number_format((int)$wallet->balance_minor);

		// آخرین 5 تراکنش
		$txs = $wallet->transactions()->latest()->limit(5)->get();

		if ($txs->isEmpty()) {
			$list = "هنوز تراکنشی ندارید.";
		} else {
			$lines = [];
			foreach ($txs as $tx) {
				$amount = (int)($tx->amount_minor ?? 0);
				$sign   = $amount >= 0 ? '+' : '-';
				$amountFmt = number_format(abs($amount));

				$desc = '';
				// تلاش برای برداشتن توضیح از description یا meta
				if (isset($tx->description) && $tx->description) {
					$desc = (string)$tx->description;
				} elseif (isset($tx->meta) && is_array($tx->meta)) {
					$desc = $tx->meta['title'] ?? $tx->meta['reason'] ?? '';
				}

				$when = method_exists($tx, 'getAttribute') && $tx->getAttribute('created_at')
					? $tx->created_at->format('Y-m-d H:i')
					: '';

				$parts = array_filter([
					$sign . $amountFmt . ' تومان',
					$desc ?: null,
					$when ?: null,
				]);

				$lines[] = '• ' . implode(' - ', $parts);
			}
			$list = implode("\n", $lines);
		}

		$text = $this->msg->render('WalletTopupText', [
			'balance' => $balanceFmt,
			'list'    => $list,
		]);

		// برای جلوگیری از خطای "message is not modified" از safeEditMessage استفاده کنیم
		$this->tg->safeEditMessage($owner->telegram_bot_token, (int)$chatId, (int)$messageId, $text, $this->ikb->backToMenu());
	}
}
