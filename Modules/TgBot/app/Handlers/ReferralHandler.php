<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Client\Models\Client;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\InlineKeyboardService;
use Modules\TgBot\Services\TelegramApiService;
use Telegram\Bot\Api;

class ReferralHandler implements Handler
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

		$from      = $cb['from'] ?? [];
		$telegramId = (string)($from['id'] ?? '');
		if ($telegramId === '') return;

		$client = Client::query()
			->where('user_id', $owner->id)
			->where('telegram_id', $telegramId)
			->first();

		if (!$client) return;

		$client->ensureReferralCode();

		$username = Cache::remember("tg:bot_username:{$owner->id}", 86400, function () use ($owner) {
			$api = new Api($owner->telegram_bot_token);
			$me  = $api->getMe();
			return $me->username ?? '';
		});

		$refLink = $username
			? "https://t.me/{$username}?start=ref_{$client->referral_code}"
			: "https://t.me/{$owner->telegram_webhook}?start=ref_{$client->referral_code}"; // fallback ضعیف

		$count = $client->referrals()->count();

		$text = $this->msg->render('ReferralText', [
			'ref_link'  => $refLink,
			'ref_count' => (string)$count,
		]);

		// ترجیح با safeEditMessage طبق الگوی پروژه
		$this->tg->safeEditMessage($owner->telegram_bot_token, $chatId, (int)$messageId, $text, $this->ikb->backToMenu());
	}
}
