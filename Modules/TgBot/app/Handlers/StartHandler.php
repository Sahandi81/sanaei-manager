<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\KeyboardService;
use Modules\TgBot\Services\TelegramApiService;
use Modules\TgBot\Services\TelegramClientService;

class StartHandler implements Handler
{
	public function __construct(
		protected TelegramClientService $clients,
		protected TelegramApiService $tg,
		protected BotMessageService $msg,
		protected KeyboardService $kb
	) {}

	public function handle(User $owner, array $update): void
	{
		$message = $update['message'] ?? [];
		$chatId  = $message['chat']['id'] ?? null;
		if (!$chatId) return;

		$client = $this->clients->ensureClientForUser($owner, $message['from'] ?? []);
		$key = $client->wasRecentlyCreated ? 'StartFirstTime' : 'StartWelcomeBack';

		$text = $this->msg->render($key, [
			'name'       => $client->name,
			'bot_name'   => 'Satify VPN',
			'bot_id'     => '@satifyvpn_bot',
			'support_id' => '@Satify_supp',
		]);

		$this->tg->sendMessage($owner->telegram_bot_token, $chatId, $text, $this->kb->buildReplyKeyboard());
	}
}
