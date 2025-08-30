<?php

namespace Modules\TgBot\Services;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Handlers\MenuHandler;
use Modules\TgBot\Handlers\StartHandler;
use Modules\TgBot\Handlers\BuyConfigHandler;
use Modules\TgBot\Handlers\MyConfigsHandler;
use Modules\TgBot\Handlers\WalletTopupHandler;
use Modules\TgBot\Handlers\ProfileHandler;
use Modules\TgBot\Handlers\ReferralHandler;
use Modules\TgBot\Handlers\TipsHandler;
use Modules\TgBot\Handlers\TutorialsHandler;
use Modules\TgBot\Handlers\ConfigDetailsHandler;
use Modules\TgBot\Handlers\SupportHandler;
use Modules\TgBot\Handlers\AdminTxHandler;
use Modules\TgBot\Support\BotActions;

class UpdateRouter
{
	public function __construct(
		protected KeyboardService $keyboard,
		protected StartHandler $start,
		protected MenuHandler $menu,
		protected BuyConfigHandler $buy,
		protected MyConfigsHandler $myConfigs,
		protected WalletTopupHandler $wallet,
		protected ProfileHandler $profile,
		protected ReferralHandler $referral,
		protected TipsHandler $tips,
		protected TutorialsHandler $tutorials,
		protected ConfigDetailsHandler $configDetails,
		protected SupportHandler $support,
	) {}

	public function dispatch(User $owner, array $update): void
	{
		if (isset($update['message'])) {
			$msg = $update['message'];
			$text = (string)($msg['text'] ?? '');
			$chatType = $msg['chat']['type'] ?? null;
			$chatId = $msg['chat']['id'] ?? null;

			if ($chatId && (string)$chatId === (string)$owner->telegram_id) {
				$currentKey = sprintf('tg:await_reject_reason_current:%d', $owner->id);
				$txId = (int)(Cache::get($currentKey) ?: 0);
				if ($txId && Cache::get(sprintf('tg:await_reject_reason:%d:%d', $owner->id, $txId))) {
					app(AdminTxHandler::class)->handleAdminMessage($owner, $update);
					return;
				}
			}

			if ($chatType === 'private' && Str::startsWith(trim($text), '/start')) {
				$this->start->handle($owner, $update);
				$this->menu->handle($owner, $update);
				return;
			}

			if (!empty($msg['photo'])) {
				app(\Modules\TgBot\Handlers\ReceiptHandler::class)->handle($owner, $update);
				return;
			}

			if ($this->keyboard->isMenuToggle($text)) {
				$this->menu->handle($owner, $update);
				return;
			}

			return;
		}

		if (isset($update['callback_query'])) {
			$data = (string)($update['callback_query']['data'] ?? '');
			$base = explode(':', $data, 2)[0] ?: BotActions::MENU;

			if (in_array($base, ['TXAPPROVE', 'TXREJECT'], true)) {
				app(AdminTxHandler::class)->handle($owner, $update);
				return;
			}

			$this->resolveAction($base)->handle($owner, $update);
			return;
		}
	}

	protected function resolveAction(string $action): Handler
	{
		return match ($action) {
			BotActions::MENU     => $this->menu,
			BotActions::MY       => $this->myConfigs,
			BotActions::WALLET   => $this->wallet,
			BotActions::PROFILE  => $this->profile,
			BotActions::REF      => $this->referral,
			BotActions::TIPS     => $this->tips,
			BotActions::TUT      => $this->tutorials,
			BotActions::DETAILS  => $this->configDetails,
			BotActions::SUPPORT  => $this->support,
			BotActions::BUY      => $this->buy,
			BotActions::PAYWALLET=> $this->buy,
			BotActions::PAYCARD  => $this->buy,
			default              => $this->menu,
		};
	}
}
