<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Modules\Finance\Models\Transaction;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\TelegramApiService;
use Modules\Finance\Services\TransactionApprovalService;
use Modules\Shop\Services\OrderQrService;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Support\BotActions;

class AdminTxHandler implements Handler
{
	private const REJECT_AWAIT_TTL_MIN = 30;
	private const KEY_AWAIT            = 'tg:await_reject_reason:%d:%d';
	private const KEY_CURRENT          = 'tg:await_reject_reason_current:%d';

	public function __construct(
		protected TelegramApiService $tg,
		protected TransactionApprovalService $approver,
		protected OrderQrService $qr,
		protected BotMessageService $msg
	) {}

	public function handle(User $owner, array $update): void
	{
		if (!empty($update['message'])) {
			$this->handleAdminMessage($owner, $update);
			return;
		}

		$cb = $update['callback_query'] ?? [];
		$data = (string)($cb['data'] ?? '');
		$chatId = $cb['message']['chat']['id'] ?? null;
		if (!$chatId || !$data) return;
		if ((string)$chatId !== (string)$owner->telegram_id) return;

		[$base, $idStr] = array_pad(explode(':', $data, 2), 2, null);
		$txId = (int)($idStr ?? 0);
		if (!$txId) return;

		$tx = Transaction::find($txId);
		if (!$tx || $tx->user_id !== $owner->id) return;

		// Block if already finalized
		if (in_array($base, ['TXAPPROVE','TXREJECT'], true) && $this->isFinalized($tx)) {
			$this->notifyFinalized($owner, $cb, $chatId);
			return;
		}

		if ($base === 'TXAPPROVE') {
			[$order, $qrRel] = $this->approver->approve($tx, $owner->id);

			$adminMsg = $this->msg->render('AdminTxApproved', ['tid' => (string)$tx->id]);
			$this->tg->sendMessage($owner->telegram_bot_token, $chatId, $this->escapeMd($adminMsg), null, 'MarkdownV2');

			$clientChat = optional($tx->client)->telegram_id;
			if ($clientChat && $order) {
				$rel = $qrRel ?: $this->qr->ensure($order);
				$absPath = $this->qr->absolutePath((string)$rel);
				$subsUrl = route('shop.orders.subs', $order->subs);

				$caption = $this->msg->render('UserOrderActivatedCaption', [
					'subs_url' => $subsUrl,
					'traffic'  => (string)$order->traffic_gb,
					'days'     => (string)$order->duration_days,
				]);

				$kb = [
					'inline_keyboard' => [
						[
							['text' => tr_helper('bot','btn_my_configs_inline'), 'callback_data' => 'MY'],
						],
						[
							['text' => tr_helper('bot','btn_back_to_menu'), 'callback_data' => BotActions::MENU],
						],
					],
				];

				$this->tg->sendPhoto($owner->telegram_bot_token, $clientChat, $absPath, $caption, $kb, null);
			}
			return;
		}

		if ($base === 'TXREJECT') {
			$this->beginRejectFlow($owner, $tx, $cb);
			return;
		}
	}

	private function beginRejectFlow(User $owner, Transaction $tx, array $cb): void
	{
		$chatId = $cb['message']['chat']['id'];
		$token = $owner->telegram_bot_token;

		$awaitKey = sprintf(self::KEY_AWAIT, $owner->id, $tx->id);
		Cache::put($awaitKey, true, now()->addMinutes(self::REJECT_AWAIT_TTL_MIN));
		Cache::put(sprintf(self::KEY_CURRENT, $owner->id), $tx->id, now()->addMinutes(self::REJECT_AWAIT_TTL_MIN));

		$ask = $this->msg->render('AdminAskRejectReason', ['tid' => (string)$tx->id]);
		$this->tg->sendMessage($token, $chatId, $this->escapeMd($ask), null, 'MarkdownV2');

		if (!empty($cb['id'])) {
			$this->tg->answerCallbackQuery($token, $cb['id'], tr_helper('bot','AdminAskRejectReasonCb'));
		}
	}

	public function handleAdminMessage(User $owner, array $update): void
	{
		$msg = $update['message'] ?? [];
		$chatId = $msg['chat']['id'] ?? null;
		$text = isset($msg['text']) ? trim((string)$msg['text']) : '';

		if (!$chatId || (string)$chatId !== (string)$owner->telegram_id) return;
		if ($text === '') return;

		$currentKey = sprintf(self::KEY_CURRENT, $owner->id);
		$txId = (int)(Cache::get($currentKey) ?: 0);
		if (!$txId) {
			$this->tg->sendMessage($owner->telegram_bot_token, $chatId, tr_helper('bot','AdminRejectReasonNoPending'));
			return;
		}

		$awaitKey = sprintf(self::KEY_AWAIT, $owner->id, $txId);
		if (!Cache::get($awaitKey)) {
			$this->tg->sendMessage($owner->telegram_bot_token, $chatId, tr_helper('bot','AdminRejectReasonNoPending'));
			return;
		}

		$tx = Transaction::find($txId);
		if (!$tx || $tx->user_id !== $owner->id) {
			Cache::forget($awaitKey);
			Cache::forget($currentKey);
			$this->tg->sendMessage($owner->telegram_bot_token, $chatId, tr_helper('bot','AdminRejectReasonNoTx'));
			return;
		}

		if (defined('\Modules\Finance\Models\Transaction::STATUS_REJECTED')) {
			$tx->update([
				'status'           => Transaction::STATUS_REJECTED,
				'modified_by'      => $owner->id,
				'rejection_reason' => mb_substr($text, 0, 500),
			]);
		} else {
			$tx->update([
				'description'      => trim(($tx->description ?: '') . ' | rejected'),
				'modified_by'      => $owner->id,
				'rejection_reason' => mb_substr($text, 0, 500),
			]);
		}

		Cache::forget($awaitKey);
		Cache::forget($currentKey);

		$adminMsg = $this->msg->render('AdminTxRejected', ['tid' => (string)$tx->id]);
		$adminMsg .= "\n" . $this->msg->render('AdminRejectReasonSaved', ['reason' => $text]);
		$this->tg->sendMessage($owner->telegram_bot_token, $chatId, $this->escapeMd($adminMsg), null, 'MarkdownV2');

		$clientChat = optional($tx->client)->telegram_id;
		if ($clientChat) {
			$userMsg = $this->msg->render('UserTxRejectedWithReason', ['reason' => $text]);
			$this->tg->sendMessage($owner->telegram_bot_token, $clientChat, $this->escapeMd($userMsg), null, 'MarkdownV2');
		}
	}

	private function isFinalized(Transaction $tx): bool
	{
		$finals = [];
		if (defined('\Modules\Finance\Models\Transaction::STATUS_APPROVED')) {
			$finals[] = Transaction::STATUS_APPROVED;
		}
		if (defined('\Modules\Finance\Models\Transaction::STATUS_REJECTED')) {
			$finals[] = Transaction::STATUS_REJECTED;
		}
		if ($finals) {
			return in_array($tx->status, $finals, true);
		}
		// Fallback heuristic if constants aren’t available
		if (!empty($tx->verified_at)) return true;
		if (is_string($tx->description) && stripos($tx->description, 'rejected') !== false) return true;
		return false;
	}

	private function notifyFinalized(User $owner, array $cb, int|string $chatId): void
	{
		$token = $owner->telegram_bot_token;
		$msg = tr_helper('bot','AdminTxAlreadyFinalized');
		if (!empty($cb['id'])) {
			$this->tg->answerCallbackQuery($token, $cb['id'], $msg, true);
		} else {
			$this->tg->sendMessage($token, $chatId, $msg);
		}
	}

	private function escapeMd(string $text): string
	{
		return preg_replace('/([_\*\[\]\(\)~`>#+\-=|{}\.!])/u', '\\\\$1', $text);
	}
}
