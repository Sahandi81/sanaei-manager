<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Modules\Client\Models\Client;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\OrderActivationService;
use Modules\Shop\Services\OrderQrService;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\TelegramApiService;
use Modules\TgBot\Support\BotActions;

class TrialHandler implements Handler
{
	public function __construct(
		protected TelegramApiService $tg,
		protected OrderQrService $qr,
		protected BotMessageService $msg,
		protected OrderActivationService $activation,
	) {}

	public function handle(User $owner, array $update): void
	{
		$cb = $update['callback_query'] ?? null;
		if (!$cb) {
			return;
		}
		$botToken  = $owner->telegram_bot_token;
		$chatId    = $cb['from']['id'];
		$messageId = $cb['message']['message_id'];
		$cbId      = $cb['id'];

		try {



			$client = Client::query()
				->where('user_id', $owner->id)
				->where('telegram_id', $chatId)
				->first();

			if (!$client) {
				$msgText = $this->msg->render('TrialNoClient');
				$this->tg->answerCallbackQuery($botToken, $cbId, $msgText);
				return;
			}

			$trialProduct = Product::query()
				->where('user_id', $owner->id)
				->where('is_active', 1)
				->where('is_test', 1)
				->latest()
				->first();
			if (!$trialProduct) {
				$msgText = $this->msg->render('TrialNoProduct');
				$this->tg->answerCallbackQuery($botToken, $cbId, $msgText);
				return;
			}

			// بررسی اینکه قبلاً گرفته یا نه
			$already = Order::query()
				->where('client_id', $client->id)
				->whereHas('product', fn($q) => $q->where('is_test', 1))
				->exists();

			// اگر خواستی دوباره فعال کنی، این شرط رو باز کن
			if ($already) {
				$msgText = $this->msg->render('TrialAlreadyTaken');
				$this->tg->answerCallbackQuery($botToken, $cbId, $msgText);
				return;
			}
			$this->tg->sendMessage(
				$botToken,
				$chatId,
				"⌛ لطفاً منتظر بمانید..."
			);
			// ساخت سفارش
			$order = Order::query()->create([
				'user_id'       => $owner->id,
				'client_id'     => $client->id,
				'product_id'    => $trialProduct->id,
				'price'         => $trialProduct->price,
				'traffic_gb'    => 0.3,
				'duration_days' => $trialProduct->duration_days,
				'expires_at'    => now()->addDays($trialProduct->duration_days)->format('Y-m-d H:i:s'),
				'status'        => 0,
			]);

			# for get mysql Changes!
			$order = $order->refresh();

			$this->activation->activateOrder($order);


			$subsUrl = route('shop.orders.subs', $order->subs);

			$qrRelative = $this->qr->ensure($order);
			$qrRelative = $this->qr->absolutePath((string)$qrRelative);
			$caption = escapeMarkdownV2PreserveCode($this->msg->render('UserOrderActivatedCaption', [
				'traffic'   => $trialProduct->traffic_gb,
				'days'      => $trialProduct->duration_days,
				'subs_url'  => $subsUrl,
			]));

			$kb = [
				'inline_keyboard' => [
					[
						['text' => tr_helper('bot', 'btn_tutorials_inline'), 'url' => 'https://t.me/Satify_vpn/31'],
//						['text' => tr_helper('bot','btn_tutorials_inline'), 'callback_data' => BotActions::TUT],
					],
				],
			];

			$this->tg->sendPhoto(
				$botToken,
				$chatId,
				$qrRelative,
				$caption,
				$kb,
				'MarkdownV2'
			);


			$successText = $this->msg->render('TrialActivated');
			$this->tg->answerCallbackQuery($botToken, $cbId, $successText);

		} catch (\Throwable $e) {
			$msgText = $this->msg->render('TrialNoProduct'); // یا کلید جدید TrialFailed
			$this->tg->answerCallbackQuery($botToken, $cbId, $msgText);
		}
	}
}
