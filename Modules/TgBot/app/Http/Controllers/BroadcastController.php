<?php

namespace Modules\TgBot\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Logging\Traits\Loggable;
use Modules\TgBot\Services\BroadcastService;
use Illuminate\Http\Request;
use Modules\TgBot\Services\TelegramApiService;

class BroadcastController extends Controller
{
	use Loggable;

	/**
	 * نمایش فرم ایجاد ارسال همگانی
	 */
	public function create(): Factory|Application|View
	{
		// فقط ادمین‌ها لیست کاربران (owners) را می‌بینند
		$users = Auth::user()->role->is_admin
			? User::getActiveUsers()
			: collect(); // خالی برای غیر ادمین

		return view('tgbot::broadcasts.create', compact('users'));
	}

	/**
	 * انجام ارسال همگانی
	 *
	 * فیلدهای ورودی فرم:
	 * - text (required)                 متن پیام
	 * - parse_mode (nullable)           MarkdownV2|HTML|none
	 * - only (nullable)                 active|all  (پیش‌فرض active)
	 * - markup (nullable)               JSON یا آرایه (inline_keyboard | reply_keyboard)
	 * - user_id (nullable)              فقط برای ادمین؛ مالک بَت. غیرادمین = Auth::id()
	 */
	// اگر BroadcastRequest داری، امضای ورودی را به BroadcastRequest تغییر بده.

	public function store(Request $request, BroadcastService $broadcast, TelegramApiService $tg): RedirectResponse
	{
		$fields = $request->validate([
			'text'          => ['required', 'string', 'min:1'],
			'parse_mode'    => ['nullable', 'in:MarkdownV2,HTML,none'],
			'only'          => ['nullable', 'in:active,all,testless_active,testless_all'],
			'markup'        => ['nullable'],
			'user_id'       => ['nullable', 'exists:users,id'],
			'delivery_mode' => ['nullable', 'in:normal,test_admin'], // ← جدید
		]);

		// تعیین مالک
		$owner = (auth()->user()->role->is_admin && !empty($fields['user_id']))
			? User::query()->find($fields['user_id'])
			: auth()->user();

		$only      = $fields['only'] ?? 'active';
		$parseMode = $fields['parse_mode'] ?? 'MarkdownV2';
		if ($parseMode === 'none') $parseMode = null;

		// مارک‌آپ: JSON یا آرایه
		$replyMarkup = null;
		if (isset($fields['markup']) && $fields['markup'] !== '') {
			$replyMarkup = is_array($fields['markup']) ? $fields['markup'] : $this->safeDecodeJson($fields['markup']);
			if ($replyMarkup === null) {
				return back()->withInput()->with('error_msg', tr_helper('contents','InvalidJson') ?? 'Invalid markup JSON.');
			}
		}

		// --- حالت تست: فقط به ادمین ارسال شود ---
		$delivery = $fields['delivery_mode'] ?? 'normal';
		if ($delivery === 'test_admin') {
			if (empty($owner->telegram_id)) {
				return back()->withInput()->with('error_msg', tr_helper('contents','AdminTelegramIdMissing') ?? 'Admin telegram_id is missing.');
			}

			$text = $fields['text'];
			if ($parseMode === 'MarkdownV2') {
				$text = escapeMarkdownV2PreserveCode($text);
			}

			try {
				$tg->sendMessage(
					$owner->telegram_bot_token,
					$owner->telegram_id,
					$text,
					$replyMarkup,
					$parseMode
				);

				$this->logInfo('broadcastTestSend', 'Broadcast TEST sent to admin', [
					'owner_id' => $owner->id,
				]);

				return redirect()
					->route('tgbot.broadcasts.create')
					->with('success_msg', tr_helper('contents','BroadcastTestSentSuccessfully') ?? 'Test message sent to admin.');
			} catch (\Throwable $e) {
				$this->logError('broadcastTestSend', 'Broadcast TEST failed', [
					'owner_id' => $owner->id ?? null,
					'error'    => $e->getMessage(),
				]);
				return back()->withInput()->with('error_msg', tr_helper('contents','BroadcastFailed') ?? 'Broadcast failed. Please check logs.');
			}
		}
		try {
			$res = $broadcast->sendToOwner($owner, $fields['text'], ['only' => $only], $replyMarkup, $parseMode);

			$this->logInfo('broadcastSend', 'Broadcast executed', [
				'owner_id' => $owner->id,
				'sent'     => $res['sent'] ?? 0,
				'failed'   => $res['failed'] ?? 0,
				'total'    => $res['total'] ?? 0,
				'only'     => $only,
				'parse'    => $parseMode ?? 'none',
			]);

			$success = tr_helper('contents','BroadcastSentSuccessfully') ?? 'Broadcast sent successfully.';
			$stats   = " (total: {$res['total']}, sent: {$res['sent']}, failed: {$res['failed']})";

			return redirect()->route('tgbot.broadcasts.create')->with('success_msg', $success . $stats);
		} catch (\Throwable $e) {
			$this->logError('broadcastSend', 'Broadcast failed', [
				'owner_id' => $owner->id ?? null,
				'error'    => $e->getMessage(),
			]);
			return back()->withInput()->with('error_msg', tr_helper('contents','BroadcastFailed') ?? 'Broadcast failed. Please check logs.');
		}
	}

	/**
	 * دیکُد امن JSON برای فیلد markup
	 */
	private function safeDecodeJson($value): ?array
	{
		try {
			$decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
			return is_array($decoded) ? $decoded : null;
		} catch (\Throwable) {
			return null;
		}
	}
}
