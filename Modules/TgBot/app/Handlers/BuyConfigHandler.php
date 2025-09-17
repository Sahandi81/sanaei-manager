<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Client\Models\Client;
use Modules\Finance\Models\Card;
use Modules\Finance\Models\Transaction;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\Product;
use Modules\Shop\Services\OrderActivationService;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\InlineKeyboardService;
use Modules\TgBot\Services\TelegramApiService;
use Modules\TgBot\Services\TelegramClientService;
use Modules\TgBot\Support\BotActions;

class BuyConfigHandler implements Handler
{
	public function __construct(
		protected TelegramApiService $tg,
		protected BotMessageService $msg,
		protected InlineKeyboardService $ikb,
		protected TelegramClientService $clients
	) {}

	public function handle(User $owner, array $update): void
	{
		$cb   = $update['callback_query'] ?? [];
		$m    = $cb['message'] ?? [];
		$cbId = $cb['id'] ?? null;

		$chatId    = $m['chat']['id'] ?? null;
		$messageId = $m['message_id'] ?? null;
		if (!$chatId || !$messageId) return;

		$data = (string)($cb['data'] ?? 'BUY');

		if (Str::startsWith($data, 'BUY:')) {
			$id = (int) Str::after($data, 'BUY:');
			$this->showProductDetails($owner, $chatId, (int)$messageId, $id, $cbId);
			return;
		}

		if (Str::startsWith($data, 'PAYWALLET:')) {
			$id = (int) Str::after($data, 'PAYWALLET:');
			$this->payWithWallet($owner, $update, $chatId, (int)$messageId, $id, $cbId);
			return;
		}

		if (Str::startsWith($data, 'PAYCARD:')) {
			$id = (int) Str::after($data, 'PAYCARD:');
			$this->payWithCard($owner, $update, $chatId, (int)$messageId, $id, $cbId);
			return;
		}

		$this->showProductList($owner, $chatId, (int)$messageId, $cbId);
	}

	protected function showProductList(User $owner, int|string $chatId, int $messageId, ?string $cbId = null): void
	{
		$products = Product::query()
			->where('user_id', $owner->id)
			->where('is_active', 1)
			->where('is_test', 0)
			->orderBy('price')
			->get();

		if ($products->isEmpty()) {
			$text = $this->msg->render('BuyConfigEmpty');
			$kb   = $this->ikb->backToMenu();
			$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $kb, null, $cbId);
			return;
		}

		$text = $this->msg->render('BuyConfigListTitle');

		$rows = [];
		foreach ($products as $p) {
			$label = sprintf('%s • %s ت', $p->name, number_format((int)$p->price));
			$rows[] = [
				['text' => $label, 'callback_data' => 'BUY:' . $p->id],
			];
		}
		$rows[] = [
			['text' => tr_helper('bot', 'btn_back_to_menu'), 'callback_data' => BotActions::MENU],
		];
		$kb = ['inline_keyboard' => $rows];

		$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $kb, null, $cbId);
	}

	protected function showProductDetails(User $owner, int|string $chatId, int $messageId, int $productId, ?string $cbId = null): void
	{
		$p = Product::query()
			->where('user_id', $owner->id)
			->where('id', $productId)
			->where('is_active', 1)
			->first();

		if (!$p) {
			$this->showProductList($owner, $chatId, $messageId, $cbId);
			return;
		}

		$text = $this->msg->render('BuyConfigDetails', [
			'name'       => $p->name,
			'traffic'    => (string) $p->traffic_gb,
			'days'       => (string) $p->duration_days,
			'price'      => number_format((int)$p->price),
			'user_limit' => (string) $p->user_limit,
		]);
		$text = escapeMarkdownV2PreserveCode($text);

		$kb = [
			'inline_keyboard' => [
				[
					['text' => tr_helper('bot', 'btn_pay_wallet'), 'callback_data' => 'PAYWALLET:' . $p->id],
				],
				[
					['text' => tr_helper('bot', 'btn_pay_card2card'), 'callback_data' => 'PAYCARD:' . $p->id],
				],
				[
					['text' => tr_helper('bot', 'btn_back_to_menu'), 'callback_data' => BotActions::MENU],
				],
			],
		];

		$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $kb, 'MarkdownV2', $cbId);
	}

	protected function payWithWallet(User $owner, array $update, int|string $chatId, int $messageId, int $productId, ?string $cbId = null): void
	{
		$p = Product::query()
			->where('user_id', $owner->id)
			->where('id', $productId)
			->where('is_active', 1)
			->first();

		if (!$p) {
			$this->showProductList($owner, $chatId, $messageId, $cbId);
			return;
		}

		$client = $this->resolveClient($owner, $update);
		if (!$client) {
			$text = $this->msg->render('WalletPayFailed');
			$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $this->ikb->backToMenu(), null, $cbId);
			return;
		}

		$price = (int) $p->price;
		$walletBinding = 'Modules\Finance\Services\WalletService';
		if (!app()->bound($walletBinding)) {
			$text = $this->msg->render('WalletPayNotConfigured');
			$kb = [
				'inline_keyboard' => [
					[
						['text' => tr_helper('bot', 'btn_pay_card2card'), 'callback_data' => 'PAYCARD:' . $p->id],
					],
					[
						['text' => tr_helper('bot', 'btn_back_to_menu'), 'callback_data' => BotActions::MENU],
					],
				],
			];
			$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $kb, null, $cbId);
			return;
		}

		$wallet = app($walletBinding);

		$hasBalance = null;
		if (method_exists($wallet, 'hasSufficientBalance')) {
			$hasBalance = (bool) $wallet->hasSufficientBalance($owner->id, $price);
		} elseif (method_exists($wallet, 'balance')) {
			$bal = (int) $wallet->balance($owner->id);
			$hasBalance = $bal >= $price;
		} else {
			$hasBalance = false;
		}

		if (!$hasBalance) {
			$text = $this->msg->render('WalletPayInsufficient', [
				'price' => number_format($price),
			]);
			$kb = [
				'inline_keyboard' => [
					[
						['text' => tr_helper('bot', 'btn_wallet_topup_inline'), 'callback_data' => 'WALLET'],
					],
					[
						['text' => tr_helper('bot', 'btn_back_to_menu'), 'callback_data' => BotActions::MENU],
					],
				],
			];
			$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $kb, 'MarkdownV2', $cbId);
			return;
		}

		$debited = false;
		if (method_exists($wallet, 'debit')) {
			$debited = (bool) $wallet->debit($owner->id, $price, ['reason' => 'product_purchase', 'product_id' => $p->id]);
		} elseif (method_exists($wallet, 'withdraw')) {
			$debited = (bool) $wallet->withdraw($owner->id, $price, 'product_purchase');
		} elseif (method_exists($wallet, 'charge')) {
			$debited = (bool) $wallet->charge($owner->id, -$price, ['reason' => 'product_purchase', 'product_id' => $p->id]);
		}

		if (!$debited) {
			$text = $this->msg->render('WalletPayFailed');
			$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $this->ikb->backToMenu(), null, $cbId);
			return;
		}

		[$order, $transaction] = $this->createOrderAndTransaction($owner, $client, $p, Transaction::STATUS_PENDING);

		$transaction->update([
			'status'      => Transaction::STATUS_APPROVED,
			'verified_at' => now(),
			'modified_by' => $owner->id,
		]);
		app(OrderActivationService::class)->activateOrder($order);

		$text = $this->msg->render('WalletPaySuccess', [
			'name'  => $p->name,
			'price' => number_format($price),
		]);
		$kb = [
			'inline_keyboard' => [
				[
					['text' => tr_helper('bot', 'btn_my_configs_inline'), 'callback_data' => 'MY'],
				],
				[
					['text' => tr_helper('bot', 'btn_back_to_menu'), 'callback_data' => BotActions::MENU],
				],
			],
		];
		$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $kb, 'MarkdownV2', $cbId);

		$this->notifyAdmin($owner, $this->msg->render('AdminNotifyWallet', [
			'client' => $client->name,
			'name'   => $p->name,
			'price'  => number_format($price),
			'oid'    => (string)$order->id,
			'tid'    => (string)$transaction->id,
		]));
	}

	protected function payWithCard(User $owner, array $update, int|string $chatId, int $messageId, int $productId, ?string $cbId = null): void
	{
		$p = Product::query()
			->where('user_id', $owner->id)
			->where('id', $productId)
			->where('is_active', 1)
			->first();

		if (!$p) {
			$this->showProductList($owner, $chatId, $messageId, $cbId);
			return;
		}

		$client = $this->resolveClient($owner, $update);
		if (!$client) {
			$text = $this->msg->render('Card2CardDisabled');
			$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $this->ikb->backToMenu(), null, $cbId);
			return;
		}

		$default = Card::query()
			->where('user_id', $owner->id)
			->where('is_default', true)
			->first();

		if (!$default) {
			$hasAny = Card::query()->where('user_id', $owner->id)->exists();
			$text = $hasAny
				? $this->msg->render('Card2CardNoDefault')
				: $this->msg->render('Card2CardDisabled');

			$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $this->ikb->backToMenu(), null, $cbId);
			return;
		}

		// Create order + pending transaction
		[$order, $transaction] = $this->createOrderAndTransaction($owner, $client, $p, \Modules\Finance\Models\Transaction::STATUS_PENDING);

		// Remember pending receipt for this client (owner+client) for 2 hours
		$cacheKey = "tg:pending_receipt:{$owner->id}:{$client->id}";
		Cache::put($cacheKey, $transaction->id, now()->addHours(2));

		// Show payment instruction with backticks for copy, MarkdownV2
		$text = $this->msg->render('Card2CardPayText', [
			'price'       => number_format((int)$p->price),
			'bank_name'   => (string) $default->bank_name,
			'card_number' => $this->formatCardNumber((string) $default->card_number),
			'owner_name'  => (string) $default->owner_name,
		]);
		$kb = [
			'inline_keyboard' => [
				[
					['text' => tr_helper('bot', 'btn_back_to_menu'), 'callback_data' => BotActions::MENU],
				],
			],
		];
		$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, $messageId, $text, $kb, 'MarkdownV2', $cbId);

		// Tell user to send receipt photo
		$hint = $this->msg->render('Card2CardSendReceiptHint');
		$this->tg->sendMessage($owner->telegram_bot_token, $chatId, $hint, null, 'MarkdownV2');
	}

	public function createOrderAndTransaction(User $owner, Client $client, Product $product, string $txStatus): array
	{
		$order = Order::query()->create([
			'user_id'       => $owner->id,
			'client_id'     => $client->id,
			'product_id'    => $product->id,
			'price'         => $product->price,
			'traffic_gb'    => $product->traffic_gb,
			'duration_days' => $product->duration_days,
			'expires_at'    => now()->addDays($product->duration_days)->format('Y-m-d H:i:s'),
			'status'        => 0,
		]);
		if ($product->price > 0){
			$transaction = Transaction::query()->create([
				'user_id'     => $owner->id,
				'client_id'   => $client->id,
				'amount'      => $product->price,
				'currency'    => 'IRR',
				'description' => "Payment for product: {$product->name}",
				'status'      => $txStatus,
				'type'        => Transaction::TYPE_PANEL,
				'item_type'   => Order::class,
				'item_id'     => $order->id,
			]);
		}

		return [$order, $transaction ?? []];
	}

	protected function resolveClient(User $owner, array $update): ?Client
	{
		$from = $update['callback_query']['from'] ?? [];
		$tid  = (string)($from['id'] ?? '');
		if ($tid === '') return null;

		$client = Client::query()
			->where('user_id', $owner->id)
			->where('telegram_id', $tid)
			->first();

		if ($client) return $client;

		return $this->clients->ensureClientForUser($owner, $from);
	}

	protected function notifyAdmin(User $owner, string $text): void
	{
		$adminChatId = $owner->telegram_id;
		if (!$adminChatId) return;

		// escape only outside of `code` spans
		$safe = escapeMarkdownV2PreserveCode($text);

		$this->tg->sendMessage($owner->telegram_bot_token, $adminChatId, $safe, null, 'MarkdownV2');
	}

	protected function formatCardNumber(string $digits): string
	{
		$d = preg_replace('/\D+/', '', $digits);
		return trim(implode(' ', str_split($d, 4)));
	}
}
