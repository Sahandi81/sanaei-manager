<?php

namespace Modules\TgBot\Services;

class KeyboardService
{
	public function buildReplyKeyboard(): array
	{
		return [
			'keyboard' => [
				[tr_helper('bot', 'btn_menu_toggle')],
			],
			'resize_keyboard' => true,
			'is_persistent'   => true,
		];
	}

	public function isMenuToggle(?string $text): bool
	{
		if (!$text) return false;
		return trim($text) === tr_helper('bot', 'btn_menu_toggle');
	}
}
