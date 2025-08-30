<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Modules\Client\Models\Client;
use Modules\Shop\Models\Order;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\InlineKeyboardService;
use Modules\TgBot\Services\TelegramApiService;
use Modules\Shop\Services\OrderQrService;
use Modules\TgBot\Support\BotActions;

class MyConfigsHandler implements Handler
{
	public function __construct(
		protected TelegramApiService   $tg,
		protected BotMessageService    $msg,
		protected InlineKeyboardService $ikb,
		protected OrderQrService       $qr
	) {}

	public function handle(User $owner, array $update): void
	{
		$cb       = $update['callback_query'] ?? [];
		$m        = $cb['message'] ?? [];
		$chatId   = $m['chat']['id'] ?? null;
		$messageId= $m['message_id'] ?? null;
		if (!$chatId || !$messageId) return;

		$data = (string)($cb['data'] ?? '');
		[, $arg] = array_pad(explode(':', $data, 2), 2, null);
		$orderId = $arg ? (int)$arg : null;

		if ($orderId) {
			$this->showConfigDetails($owner, (int)$chatId, (int)$messageId, $orderId);
			return;
		}

		$this->showConfigsList($owner, (int)$chatId, (int)$messageId);
	}

	protected function showConfigsList(User $owner, int $chatId, int $messageId): void
	{
		$client = $this->resolveClient($owner->id, (string)$chatId);
		$orders = $client
			? Order::query()->where('user_id', $owner->id)->where('client_id', $client->id)->latest('id')->limit(20)->get()
			: collect();

		$text = $this->msg->render('MyConfigsText') ?: "یکی از سرویس‌هاتو انتخاب کن و مشخصات کاملش رو ببین :";
		$kb = $this->buildConfigsKeyboard($orders);

		$this->tg->editMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $kb);
	}

	protected function showConfigDetails(User $owner, int $chatId, int $messageId, int $orderId): void
	{
		$order = Order::query()->where('user_id', $owner->id)->where('id', $orderId)->first();
		if (!$order) {
			$this->tg->sendMessage($owner->telegram_bot_token, $chatId, "کانفیگ پیدا نشد یا در دسترس نیست.", $this->ikb->backToMenu());
			return;
		}

		$rel = $this->qr->ensure($order);
		$abs = $this->qr->absolutePath((string)$rel);

		$status = $this->isActive($order)
			? $this->msg->render('ConfigStatusActive')
			: $this->msg->render('ConfigStatusInactive');

		$name = $this->orderDisplayName($order);
		$subsUrl = $order->subs ? route('shop.orders.subs', $order->subs) : null;
		$plan = $this->productTitle($order) ?: '-';

		$purchasedAt = $order->created_at ? Carbon::parse($order->created_at)->format('Y-m-d H:i') : '-';
		$expiresAt   = $order->expires_at ? Carbon::parse($order->expires_at)->format('Y-m-d H:i') : '-';

		$fmt = static function (?float $v): ?string {
			if ($v === null) return null;
			$s = rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
			return $s . ' GB';
		};

		$totalTraffic = is_numeric($order->traffic_gb ?? null) ? (float)$order->traffic_gb : null;
		$usedTrafficV = is_numeric($order->used_traffic_gb ?? null) ? (float)$order->used_traffic_gb : null;

		if ($totalTraffic !== null && $usedTrafficV !== null) {
			$usedTrafficV = min($usedTrafficV, $totalTraffic);
		}

		$remainTrafficV = ($totalTraffic !== null)
			? max(0.0, $totalTraffic - (float)($usedTrafficV ?? 0.0))
			: null;

		$usedTraffic = $fmt($usedTrafficV);
		$remainTraffic = $fmt($remainTrafficV);

		$remainDays = $order->expires_at
			? max(0, Carbon::now()->startOfDay()->diffInDays(Carbon::parse($order->expires_at)->startOfDay(), false))
			: null;

		$subsBlock           = $subsUrl       ? $this->msg->render('ConfigSubsBlock', ['url' => $subsUrl]) : '';
		$usedTrafficBlock    = $usedTraffic   ? $this->msg->render('ConfigUsedTrafficBlock', ['used' => $usedTraffic]) : '';
		$remainTrafficBlock  = $remainTraffic ? $this->msg->render('ConfigRemainTrafficBlock', ['remain' => $remainTraffic]) : '';
		$remainDaysBlock     = is_numeric($remainDays) ? $this->msg->render('ConfigRemainDaysBlock', ['days' => (string)$remainDays]) : '';

		$caption = $this->msg->render('ConfigDetailsCaption', [
			'status'                => $status,
			'name'                  => $name,
			'subs_block'            => $subsBlock,
			'plan'                  => $plan,
			'purchased_at'          => $purchasedAt,
			'expires_at'            => $expiresAt,
			'used_traffic_block'    => $usedTrafficBlock,
			'remain_traffic_block'  => $remainTrafficBlock,
			'remain_days_block'     => $remainDaysBlock,
		]);

		$kb = [
			'inline_keyboard' => [
				[
					['text' => tr_helper('bot','btn_back_to_menu'), 'callback_data' => \Modules\TgBot\Support\BotActions::MENU],
				],
			],
		];

		$this->tg->sendPhoto($owner->telegram_bot_token, $chatId, $abs, $caption, $kb, null);
	}

	protected function buildConfigsKeyboard($orders): array
	{
		$rows = [];
		foreach ($orders as $o) {
			$rows[] = [
				[
					'text' => $this->orderDisplayName($o),
					'callback_data' => 'MY:' . $o->id,
				],
			];
		}

		// extra actions
//		$rows[] = [
//			['text' => '🔎 جستجوی کانفیگ', 'callback_data' => 'MY'], // stub; wire to your search flow if any
//		];
		$rows[] = [
			['text' => tr_helper('bot','btn_back_to_menu'), 'callback_data' => BotActions::MENU],
		];

		return ['inline_keyboard' => $rows];
	}

	protected function resolveClient(int $ownerId, string $telegramChatId)
	{
		// Adjust model/columns if your Client differs
		return Client::query()
			->where('user_id', $ownerId)
			->where('telegram_id', $telegramChatId)
			->first();
	}

	protected function orderDisplayName($order): string
	{
		// Prefer a stable, user-facing label
		$candidates = [
			Arr::get($order, 'label'),
			Arr::get($order, 'title'),
			Arr::get($order, 'name'),
			Arr::get($order, 'product.name'),
		];
		foreach ($candidates as $c) {
			if (is_string($c) && $c !== '') return $c;
		}
		return 'Config-' . $order->id;
	}

	protected function productTitle($order): ?string
	{
		$p = $order->product ?? null;
		if (!$p) return Arr::get($order, 'product_title') ?: null;
		return $p->name ?? ($p->title ?? null);
	}

	protected function isActive($order): bool
	{
		if (property_exists($order, 'status') && method_exists($order, 'isActive')) {
			return (bool)$order->isActive();
		}
		if (!empty($order->expires_at)) {
			return now()->lt($order->expires_at);
		}
		return true;
	}

	protected function pair(string $label, ?string $value, string $emoji = ''): ?string
	{
		if (!$value) return null;
		$em = $emoji ? ($emoji . ' ') : '';
		return "{$label}: {$value} {$em}";
	}

	protected function escapeMd(string $text): string
	{
		return preg_replace('/([_\*\[\]\(\)~`>#+\-=|{}\.!])/u', '\\\\$1', $text);
	}
}
