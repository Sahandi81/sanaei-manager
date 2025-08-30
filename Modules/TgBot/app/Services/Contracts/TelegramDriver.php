<?php

namespace Modules\TgBot\Services\Contracts;

interface TelegramDriver
{
	public function sendMessage(string $token, int|string $chatId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): void;
	public function answerCallbackQuery(string $token, string $callbackId, ?string $text = null, bool $showAlert = false): void;
	public function editMessageText(string $token, int|string $chatId, int $messageId, string $text, ?array $replyMarkup = null, ?string $parseMode = null): void;
	public function deleteMessage(string $token, int|string $chatId, int $messageId): void;

	public function sendPhoto(string $token, int|string $chatId, string $photo, ?string $caption = null, ?array $replyMarkup = null, ?string $parseMode = null): void;

}
