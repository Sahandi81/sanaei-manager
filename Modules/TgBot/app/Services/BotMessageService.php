<?php

namespace Modules\TgBot\Services;

class BotMessageService
{
	public function render(string $key, array $vars = [], ?string $locale = null): string
	{
		$text = tr_helper('bot', $key);

		if (!is_string($text) || $text === '') {
			$text = $key;
		}

		return $this->interpolate($text, $vars);
	}

	protected function interpolate(string $text, array $vars): string
	{
		if (empty($vars)) {
			return $text;
		}

		$replacements = [];
		foreach ($vars as $k => $v) {
			$replacements[':' . $k] = (string) $v;
			$replacements['{' . $k . '}'] = (string) $v;
		}

		return strtr($text, $replacements);
	}
}
