<?php

namespace Modules\TgBot\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Client\Models\Client;
use Modules\Logging\Traits\Loggable;

class TelegramClientService
{
	use Loggable;

	public function ensureClientForUser(User $owner, array $from, ?string $desc = null): Client
	{
		$telegramId = (string)($from['id'] ?? '');
		if ($telegramId === '') {
			$this->logError('ensureClientForUser', 'Missing telegram id in update.from', [
				'user_id' => $owner->id,
				'from'    => $from,
			]);
			throw new \InvalidArgumentException('telegram_id یافت نشد.');
		}

		$name = trim(
			(($from['first_name'] ?? '') . ' ' . ($from['last_name'] ?? ''))
		);
		if ($name === '') {
			$name = $from['username'] ?? 'Telegram User';
		}

		$desc = $desc ?? sprintf(
			'username: @%s | lang: %s',
			$from['username'] ?? '-',
			$from['language_code'] ?? '-'
		);

		$client = Client::query()->updateOrCreate(
			['user_id' => $owner->id, 'telegram_id' => $telegramId],
			[
				'name'   => $name,
				'type'   => 'telegram',
				'desc'   => $desc,
				'status' => 1,
			]
		);


		$this->logInfo('ensureClientForUser', 'Client ensured on /start', [
			'user_id'     => $owner->id,
			'client_id'   => $client->id,
			'telegram_id' => $telegramId,
		]);

		return $client;
	}
}
