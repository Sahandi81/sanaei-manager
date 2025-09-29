<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Modules\Client\Models\Client;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\KeyboardService;
use Modules\TgBot\Services\TelegramApiService;
use Modules\TgBot\Services\TelegramClientService;
use Modules\Finance\Models\Wallet;

class StartHandler implements Handler
{
	public function __construct(
		protected TelegramClientService $clients,
		protected TelegramApiService $tg,
		protected BotMessageService $msg,
		protected KeyboardService $kb
	) {}

	public function handle(User $owner, array $update): void
	{
		$message = $update['message'] ?? [];
		$chatId  = $message['chat']['id'] ?? null;
		if (!$chatId) return;

		// ایجاد/به‌روزرسانی کلاینت از روی پیام دریافتی
		$client = $this->clients->ensureClientForUser($owner, $message['from'] ?? []);

		// اگر کاربر برای اولین‌بار استارت کرده و payload رفرال دارد، ثبت زیرمجموعه + شارژ کیف پول دعوت‌کننده
		if ($client->wasRecentlyCreated) {
			$text = (string)($message['text'] ?? '');
			$payload = '';
			if (Str::startsWith($text, '/start')) {
				$payload = trim(Str::replaceFirst('/start', '', $text));
			}

			if ($payload !== '' && Str::startsWith($payload, 'ref_')) {
				$refCode = Str::after($payload, 'ref_');

				$referrer = Client::query()
					->where('user_id', $owner->id)
					->where('referral_code', $refCode)
					->first();

				if ($referrer && $referrer->id !== $client->id && $referrer->telegram_id !== $client->telegram_id) {
					// ثبت زیرمجموعه
					$client->referrer_id = $referrer->id;
					$client->referred_at = Carbon::now();
					$client->save();

					// شارژ کیف پول دعوت‌کننده: ۲۰,۰۰۰ تومان
					$creditToman = 20000; // واحد: تومان (IRT)
					$wallet = Wallet::query()->firstOrCreate(
						[
							'owner_type' => Client::class,
							'owner_id'   => $referrer->id,
							'currency'   => 'IRT', // اگر IRR دارید، مطابق همان تغییر دهید
						],
						[
							'balance_minor' => 0,
							'status'        => Wallet::STATUS_ACTIVE,
							'meta'          => null,
						]
					);
					$wallet->increment('balance_minor', $creditToman);

					// نوتیفای با number_format
					$amountFmt = number_format($creditToman);
					try {
						$notifyText = $this->msg->render('ReferralNewJoinNotify', [
							'name'   => $client->name ?: ('#' . $client->id),
							'amount' => $amountFmt, // مثال متن: "یک کاربر با لینک شما وارد شد: :name\n+:amount تومان به کیف پول شما اضافه شد."
						]);
						$this->tg->sendMessage($owner->telegram_bot_token, (int)$referrer->telegram_id, $notifyText);
					} catch (\Throwable $e) {
					}
				}
			}
		}

		$key = $client->wasRecentlyCreated ? 'StartFirstTime' : 'StartWelcomeBack';

		$text = $this->msg->render($key, [
			'name'       => $client->name,
			'bot_name'   => $owner->bot_name ?? 'Satify VPN',
			'bot_id'     => $owner->bot_id ?? '@SatifyVPN_bot',
			'support_id' => $owner->support_id ??'@Satify_Supp',
		]);

		$this->tg->sendMessage($owner->telegram_bot_token, (int)$chatId, $text, $this->kb->buildReplyKeyboard());

	}
}
