<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\InlineKeyboardService;
use Modules\TgBot\Services\TelegramApiService;

class ProfileHandler implements Handler
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

		$text = $this->msg->render('ProfileText');
		$this->tg->editMessage($owner->telegram_bot_token, $chatId, (int)$messageId, $text, $this->ikb->backToMenu());
	}
}
