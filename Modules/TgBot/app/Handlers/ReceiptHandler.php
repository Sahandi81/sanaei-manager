<?php

namespace Modules\TgBot\Handlers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Modules\Client\Models\Client;
use Modules\Finance\Models\Transaction;
use Modules\TgBot\Handlers\Contracts\Handler;
use Modules\TgBot\Services\BotMessageService;
use Modules\TgBot\Services\TelegramApiService;
use Telegram\Bot\Api;
use Illuminate\Http\UploadedFile;
use Modules\FileManager\Facades\FileManager;

class ReceiptHandler implements Handler
{
	public function __construct(
		protected TelegramApiService $tg,
		protected BotMessageService $msg
	) {}

	public function handle(User $owner, array $update): void
	{
		$m = $update['message'] ?? [];
		$chatId = $m['chat']['id'] ?? null;
		if (!$chatId) return;

		$from = $m['from'] ?? [];
		$tid  = (string)($from['id'] ?? '');
		if ($tid === '') return;

		$client = Client::query()
			->where('user_id', $owner->id)
			->where('telegram_id', $tid)
			->first();
		if (!$client) return;

		// فقط اگر انتظار رسید برای این کلاینت ثبت شده
		$cacheKey = "tg:pending_receipt:{$owner->id}:{$client->id}";
		$txId = Cache::get($cacheKey);
		if (!$txId) return;

		$tx = Transaction::query()
			->where('user_id', $owner->id)
			->where('id', $txId)
			->where('status', Transaction::STATUS_PENDING)
			->first();
		if (!$tx) {
			Cache::forget($cacheKey);
			return;
		}

		// ۱) فایل رو از تلگرام بگیر (photo یا document)
		$fileId = $this->extractFileId($m);
		if (!$fileId) return;

		[$absPath, $originalName] = $this->downloadTelegramFile($owner->telegram_bot_token, $fileId, $tx->id);
		if (!$absPath) return;

		// ۲) به Transaction attach کن (مثل پنل)
		$this->attachToTransaction($tx, $absPath, $originalName);

		// ۳) پیام به کاربر: دریافت شد
		$ok = $this->msg->render('ReceiptReceivedUser');
		$this->tg->sendMessage($owner->telegram_bot_token, $chatId, $ok);

		// ۴) ارسال به ادمین با دکمه‌های تایید/رد (استفاده از همان file_id تلگرام برای سرعت)
		$adminChatId = $owner->telegram_id;
		if ($adminChatId) {
			$captionRaw = $this->msg->render('AdminReceiptCaption', [
				'client' => $client->name,
				'amount' => number_format((int)$tx->amount),
				'tid'    => (string)$tx->id,
				'oid'    => (string)$tx->item_id,
			]);
			$caption = $this->escapeMarkdownV2PreserveCode($captionRaw);

			$kb = [
				'inline_keyboard' => [
					[
						['text' => tr_helper('bot', 'btn_tx_approve'), 'callback_data' => 'TXAPPROVE:' . $tx->id],
						['text' => tr_helper('bot', 'btn_tx_reject'),  'callback_data' => 'TXREJECT:'  . $tx->id],
					],
				],
			];

			// ارسال دوباره همون file_id به ادمین (بدون آپلود مجدد)
			$this->tg->sendPhoto($owner->telegram_bot_token, $adminChatId, $fileId, $caption, $kb, 'MarkdownV2');
		}

		Cache::forget($cacheKey);
	}

	/** اگر photo بود بزرگ‌ترین سایز، اگر document بود همون فایل. */
	private function extractFileId(array $message): ?string
	{
		if (!empty($message['photo']) && is_array($message['photo'])) {
			$largest = end($message['photo']);
			return $largest['file_id'] ?? null;
		}
		if (!empty($message['document'])) {
			return $message['document']['file_id'] ?? null;
		}
		return null;
	}

	/**
	 * فایل تلگرام رو دانلود می‌کنه و در storage/app/public/transactions/{txId}/... ذخیره می‌کنه.
	 * خروجی: [absolutePath, originalName]
	 */
	private function downloadTelegramFile(string $token, string $fileId, int $txId): array
	{
		$api  = new Api($token);
		$resp = $api->getFile(['file_id' => $fileId]);
		$path = $resp->filePath ?? $resp->file_path ?? null;
		if (!$path) return [null, null];

		$url  = "https://api.telegram.org/file/bot{$token}/{$path}";
		$bin  = Http::get($url)->body();
		if (!$bin) return [null, null];

		$ext  = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
		$name = 'transactions/'.$txId.'/receipt_'.time().'.'.$ext;

		Storage::disk('public')->put($name, $bin);

		$abs  = Storage::disk('public')->path($name);
		$orig = basename($name);

		return [$abs, $orig];
	}

	/** مثل پنل: فایل رو به مدل Transaction سنجاق می‌کنه. */
	private function attachToTransaction(Transaction $tx, string $absPath, ?string $originalName = null): void
	{
		$mime     = @mime_content_type($absPath) ?: 'image/jpeg';
		$filename = $originalName ?: basename($absPath);

		// UploadedFile لاراول (نه Symfony). ارور را 0 بگذار که isValid=true شود.
		$uploaded = new UploadedFile(
			$absPath,
			$filename,
			$mime,
			\UPLOAD_ERR_OK,
			true // test mode
		);

		\Modules\FileManager\Facades\FileManager::store(
			$tx,
			$uploaded,
			'transactions/' . $tx->id
		);
	}

	/** Escape امن برای MarkdownV2 با حفظ `code` span ها. */
	private function escapeMarkdownV2PreserveCode(string $text): string
	{
		$parts = preg_split('/(`[^`]*`)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
		if ($parts === false) return $text;

		$escaped = '';
		foreach ($parts as $part) {
			if ($part === '') continue;
			if ($part[0] === '`') {
				$escaped .= $part;
			} else {
				$escaped .= preg_replace('/([_\*\[\]\(\)~`>#+\-=|{}\.!])/u', '\\\\$1', $part);
			}
		}
		return $escaped;
	}
}

