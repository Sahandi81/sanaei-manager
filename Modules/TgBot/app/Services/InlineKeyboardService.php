<?php

namespace Modules\TgBot\Services;

use Modules\TgBot\Support\BotActions;

class InlineKeyboardService
{
	public function main(): array
	{
		return [
			'inline_keyboard' => [
				[
					['text' => tr_helper('bot', 'btn_buy_new_config_inline'),   'callback_data' => BotActions::BUY],
					['text' => tr_helper('bot', 'btn_my_configs_inline'),       'callback_data' => BotActions::MY],
				],
				[
					['text' => tr_helper('bot', 'btn_wallet_topup_inline'),     'callback_data' => BotActions::WALLET],
				],
				[
					['text' => tr_helper('bot', 'btn_get_trial'),			     'callback_data' => BotActions::TRIAL], // 🆕 دکمه تست
				],
				[
					['text' => tr_helper('bot', 'btn_tutorials_inline'), 'url' => 'https://t.me/Satify_vpn/31'],
//					['text' => tr_helper('bot', 'btn_tutorials_inline'),        'callback_data' => BotActions::TUT],
					['text' => tr_helper('bot', 'btn_referral_inline'),         'callback_data' => BotActions::REF],
				],
				[
					['text' => tr_helper('bot', 'btn_support_inline'),          'callback_data' => BotActions::SUPPORT],
				],
			],
		];
	}

	public function backToMenu(): array
	{
		return [
			'inline_keyboard' => [
				[
					['text' => tr_helper('bot', 'btn_back_to_menu'), 'callback_data' => BotActions::MENU],
				],
			],
		];
	}
}

