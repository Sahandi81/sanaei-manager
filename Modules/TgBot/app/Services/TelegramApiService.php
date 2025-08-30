<?php

namespace Modules\TgBot\Services;

use Modules\TgBot\Services\Contracts\TelegramDriver;

class TelegramApiService
{
	public function __construct(protected TelegramDriver $driver) {}

	public function answerCallbackQuery(string $botToken, string $callbackId, ?string $text = null, bool $showAlert = false): void
	{
		$this->driver->answerCallbackQuery($botToken, $callbackId, $text, $showAlert);
	}

	public function editMessageText(string $botToken, int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): void
	{
		$this->driver->editMessageText($botToken, $chatId, $messageId, $text, $replyMarkup, $parseMode);
	}

	public function deleteMessage(string $botToken, int|string $chatId, int $messageId): void
	{
		$this->driver->deleteMessage($botToken, $chatId, $messageId);
	}

	public function sendMessage(string $botToken, int|string $chatId, string $text, ?array $inlineOrReplyMarkup = null, ?string $parseMode = null): void
	{
		$this->driver->sendMessage($botToken, $chatId, $text, $inlineOrReplyMarkup, $parseMode);
	}

	public function editMessage(string $botToken, int|string $chatId, int $messageId, string $text, ?array $inlineMarkup = null, ?string $parseMode = null): void
	{
		$this->driver->editMessageText($botToken, $chatId, $messageId, $text, $inlineMarkup, $parseMode);
	}

	public function sendPhoto(string $botToken, int|string $chatId, string $photo, ?string $caption = null, ?array $replyMarkup = null, ?string $parseMode = null): void
	{
		$this->driver->sendPhoto($botToken, $chatId, $photo, $caption, $replyMarkup, $parseMode);
	}

	public function safeEditMessage(
		string $botToken,
		int|string $chatId,
		int $messageId,
		string $text,
		?array $replyMarkup = null,
		?string $parseMode = null,
		?string $callbackId = null
	): void {
		try {
			$this->driver->editMessageText($botToken, $chatId, $messageId, $text, $replyMarkup, $parseMode);
		} catch (\Throwable $e) {
			$msg = $e->getMessage();
			if ($callbackId) {
				try { $this->driver->answerCallbackQuery($botToken, $callbackId); } catch (\Throwable $ignore) {}
			}
			if (stripos($msg, 'message is not modified') !== false) {
				return;
			}
			$this->driver->sendMessage($botToken, $chatId, $text, $replyMarkup, $parseMode);
		}
	}
}
