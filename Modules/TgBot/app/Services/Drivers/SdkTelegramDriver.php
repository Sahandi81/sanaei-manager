<?php

namespace Modules\TgBot\Services\Drivers;

use Modules\TgBot\Services\Contracts\TelegramDriver;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

class SdkTelegramDriver implements TelegramDriver
{
	protected function api(string $token): Api
	{
		return new Api($token);
	}

	public function sendMessage(string $token, int|string $chatId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): void
	{
		$params = ['chat_id' => $chatId, 'text' => $text];
		if ($parseMode) $params['parse_mode'] = $parseMode;
		if ($replyMarkup) $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
		$this->api($token)->sendMessage($params);
	}

	public function answerCallbackQuery(string $token, string $callbackId, ?string $text = null, bool $showAlert = false): void
	{
		$params = ['callback_query_id' => $callbackId];
		if ($text !== null) $params['text'] = $text;
		if ($showAlert) $params['show_alert'] = true;
		$this->api($token)->answerCallbackQuery($params);
	}

	public function editMessageText(string $token, int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): void
	{
		$params = ['chat_id' => $chatId, 'message_id' => $messageId, 'text' => $text];
		if ($parseMode) $params['parse_mode'] = $parseMode;
		if ($replyMarkup) $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE);
		$this->api($token)->editMessageText($params);
	}

	public function deleteMessage(string $token, int|string $chatId, int $messageId): void
	{
		$params = ['chat_id' => $chatId, 'message_id' => $messageId];
		$this->api($token)->deleteMessage($params);
	}


	public function sendPhoto(string $token, int|string $chatId, string $photo, ?string $caption = null, ?array $replyMarkup = null, ?string $parseMode = null): void
	{
		$params = ['chat_id' => $chatId];

		if ($this->looksLikeFileId($photo)) {
			$params['photo'] = $photo;
		} else {
			// Local path or URL
			$params['photo'] = InputFile::create($photo);
		}

		if ($caption !== null)   { $params['caption'] = $caption; }
		if ($parseMode !== null) { $params['parse_mode'] = $parseMode; }
		if ($replyMarkup)        { $params['reply_markup'] = json_encode($replyMarkup, JSON_UNESCAPED_UNICODE); }

		$this->api($token)->sendPhoto($params);
	}

	protected function looksLikeFileId(string $val): bool
	{
		if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://') || str_starts_with($val, '/')) {
			return false;
		}
		// Heuristic for Telegram file_id (long base64-ish tokens)
		return (bool)preg_match('/^[A-Za-z0-9\_\-]{20,}$/', $val);
	}
}
