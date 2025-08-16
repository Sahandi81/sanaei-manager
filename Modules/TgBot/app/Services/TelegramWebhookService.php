<?php

namespace Modules\TgBot\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Modules\Logging\Traits\Loggable;

class TelegramWebhookService
{
	use Loggable;

	/**
	 * ست کردن وبهوک تلگرام برای یک کاربر
	 * @throws \Exception
	 */
	public function setWebhookForUser(User $user): bool
	{
		$botToken = $user->telegram_bot_token;
		$webhookKey = $user->telegram_webhook;

		if (empty($botToken)) {
			$this->logError('setTelegramWebhook', 'Bot token is missing', [
				'user_id' => $user->id,
			]);
			return false;
		}

		if (empty($webhookKey)) {
			$this->logError('setTelegramWebhook', 'Webhook key is missing', [
				'user_id' => $user->id,
			]);
			return false;
		}

		$webhookUrl = url("/telegram/webhook/{$webhookKey}");

		$response = Http::timeout(15)
			->asForm()
			->post("https://api.telegram.org/bot{$botToken}/setWebhook", [
				'url' => $webhookUrl,
				// در صورت نیاز می‌تونی secret_token هم ست کنی:
				// 'secret_token' => hash('sha256', $webhookKey),
				// سایر گزینه‌ها:
				// 'allowed_updates' => json_encode(['message', 'callback_query']),
				// 'drop_pending_updates' => true,
			]);

		if (!$response->successful() || !$response->json('ok')) {
			$this->logError('setTelegramWebhook', 'Failed to set webhook', [
				'user_id'  => $user->id,
				'url'      => $webhookUrl,
				'response' => $response->body(),
			]);
			return false;
		}

		$this->logInfo('setTelegramWebhook', 'Webhook set successfully', [
			'user_id' => $user->id,
			'url'     => $webhookUrl,
		]);

		return true;
	}
}
