<?php

namespace Modules\TgBot\Services\Drivers;

use Illuminate\Support\Facades\Http;
use Modules\TgBot\Services\Contracts\TelegramDriver;

class HttpTelegramDriver implements TelegramDriver
{
	protected function api(string $token, string $method): string
	{
		return "https://api.telegram.org/bot{$token}/{$method}";
	}

	public function sendMessage(string $token, int|string $chatId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): void
	{
		$payload = ['chat_id' => $chatId, 'text' => $text];
		if ($parseMode) $payload['parse_mode'] = $parseMode;
		if ($replyMarkup) $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
		Http::asForm()->post($this->api($token, 'sendMessage'), $payload);
	}

	public function answerCallbackQuery(string $token, string $callbackId, ?string $text = null, bool $showAlert = false): void
	{
		$payload = ['callback_query_id' => $callbackId];
		if ($text !== null) $payload['text'] = $text;
		if ($showAlert) $payload['show_alert'] = true;
		Http::asForm()->post($this->api($token, 'answerCallbackQuery'), $payload);
	}

	public function editMessageText(string $token, int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): void
	{
		$payload = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text];
		if ($parseMode) $payload['parse_mode'] = $parseMode;
		if ($replyMarkup) $payload['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
		Http::asForm()->post($this->api($token, 'editMessageText'), $payload);
	}

	public function deleteMessage(string $token, int|string $chatId, int $messageId): void
	{
		$payload = ['chat_id' => $chatId, 'message_id' => $messageId];
		Http::asForm()->post($this->api($token, 'deleteMessage'), $payload);
	}
}
