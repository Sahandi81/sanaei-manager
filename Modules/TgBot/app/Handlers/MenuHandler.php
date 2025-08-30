<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\InlineKeyboardService;
use Modules\TgBot\Services\KeyboardService;
use Modules\TgBot\Services\TelegramApiService;

class MenuHandler implements Handler
{
	public function __construct(
		protected TelegramApiService $tg,
		protected BotMessageService $msg,
		protected InlineKeyboardService $ikb,
		protected KeyboardService $kb
	) {}

	public function handle(User $owner, array $update): void
	{
		if (isset($update['callback_query'])) {
			$cb  = $update['callback_query'];
			$m   = $cb['message'] ?? [];
			$chatId    = $m['chat']['id'] ?? null;
			$messageId = $m['message_id'] ?? null;
			if (!$chatId || !$messageId) return;

			$text = $this->msg->render('MenuHomeText');
			$markup = $this->ikb->main();

			$isMedia = !empty($m['photo'])
				|| !empty($m['document'])
				|| !empty($m['video'])
				|| !empty($m['animation'])
				|| !empty($m['audio'])
				|| array_key_exists('caption', $m);

			if ($isMedia) {
				try {
					$this->tg->deleteMessage($owner->telegram_bot_token, $chatId, (int)$messageId);
				} catch (\Throwable $e) {
					// ignore
				}
				$this->tg->sendMessage($owner->telegram_bot_token, $chatId, $text, $markup, null);
				if (!empty($cb['id'])) {
					$this->tg->answerCallbackQuery($owner->telegram_bot_token, $cb['id']);
				}
				return;
			}

			$this->tg->safeEditMessage(
				$owner->telegram_bot_token,
				$chatId,
				(int)$messageId,
				$text,
				$markup,
				null,
				$cb['id'] ?? null
			);
			return;
		}

		$m = $update['message'] ?? [];
		$chatId = $m['chat']['id'] ?? null;
		if (!$chatId) return;

		$text = $this->msg->render('MenuHomeText');
		$this->tg->sendMessage($owner->telegram_bot_token, $chatId, $text, $this->ikb->main());
	}
}
