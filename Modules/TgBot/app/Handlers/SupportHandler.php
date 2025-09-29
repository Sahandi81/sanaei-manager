<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\InlineKeyboardService;
use Modules\TgBot\Services\TelegramApiService;

class SupportHandler implements Handler
{
	public function __construct(
		protected TelegramApiService $tg,
		protected BotMessageService $msg,
		protected InlineKeyboardService $ikb
	) {}

	public function handle(User $owner, array $update): void
	{
		$cb = $update['callback_query'] ?? null;
		if (!$cb) return;

		$botToken  = $owner->telegram_bot_token;
		$chatId    = $cb['message']['chat']['id'] ?? null;
		$messageId = $cb['message']['message_id'] ?? null;
		$cbId      = $cb['id'] ?? null;
		if (!$chatId || !$messageId) return;

		$text = $this->msg->render('SupportText', ['support_id' => $owner->support_id ?? '@Satify_supp']);
		$text = escapeMarkdownV2PreserveCode($text);
		$kb   = $this->ikb->backToMenu(); // باید آرایه reply_markup معتبر برگرداند

		// استفاده از safeEditMessage تا خطای "message is not modified" هندل شود
		$this->tg->safeEditMessage($botToken, $chatId, (int)$messageId, $text, $kb, 'MarkdownV2', $cbId);
	}
}
