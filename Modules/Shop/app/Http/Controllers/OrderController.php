<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\ErrorCorrectionLevel as ErrorCorrectionLevelEndroid;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Modules\Client\Models\Client;
use Modules\Finance\Http\Controllers\TransactionController;
use Modules\Finance\Models\Transaction;
use Modules\Logging\Traits\Loggable;
use Modules\QrGenerator\Services\QrCodeService;
use Modules\QrGenerator\Services\QrGeneratorService;
use Modules\Shop\Http\Requests\OrderRequest;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\Product;


class OrderController extends Controller
{
	use Loggable;

	public function store(OrderRequest $request)
	{
		$fields = $request->validated();

		$client = Client::query()->findOrFail($fields['client_id']);
		$product = Product::query()->findOrFail($fields['product_id']);

		if (!auth()->user()->role->is_admin && $client->user_id !== auth()->id()) {
			return redirect()->back()->with('error_msg', tr_helper('contents', 'YouAreNotAllowedToDoThisAction'));
		}
//		dd(now()->addDays($product->duration_days)->format('Y-m-d H:i:s'));
		$order = Order::query()->create([
			'user_id'       	 	=> auth()->id(),
			'client_id'          	=> $client->id,
			'product_id'         	=> $product->id,
			'price'              	=> $product->price,
			'traffic_gb'         	=> $product->traffic_gb,
			'duration_days'	     	=> $product->duration_days,
			'expires_at'         	=> now()->addDays($product->duration_days)->format('Y-m-d H:i:s'),
			'status'            	=> 0
		]);

		$transaction = Transaction::query()->create([
			'user_id'     			=> auth()->id(),
			'client_id'   			=> $client->id,
			'amount'      			=> $product->price,
			'currency'    			=> 'IRR',
			'description'			=> "Payment for product: {$product->name}",
			'status'      			=> Transaction::STATUS_PENDING,
			'type'        			=> Transaction::TYPE_PANEL,
			'item_type'  			=> Order::class,
			'item_id'    			=> $order->id,
		]);

		// Inline approve
		(new TransactionController())->approve($transaction);

		$url = route('shop.orders.subs', $order->subs);
		$logo = base_path('/public/logo.png');


		$qrCodePath = QrGeneratorService::generateQr($url, $order->client_id, $order->id, $order->subs, $logo);

		$order->update(['qr_path' => $qrCodePath]);

		$this->logInfo('orderCreated', 'Order created with transaction', [
			'order_id' => $order->id,
			'product_id' => $product->id,
			'amount' => $product->price,
		]);

		return redirect()->back()->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}

	private function vlessInfoNode(string $text): string
	{
		// نود پیام‌رسان (نمایشی) —故 عمداً غیرقابل اتصال
		$uuid  = '00000000-0000-0000-0000-000000000000';
		$host  = 'invalid.invalid'; // دامنهٔ رزرو شده؛ هرگز resolve نمی‌شود
		$port  = 443;
		$query = 'type=tcp&security=none';
		return "vless://{$uuid}@{$host}:{$port}?{$query}#" . rawurlencode($text);
	}

	private function humanGB(int $bytes): string
	{
		$gb = $bytes / (1024 ** 3);
		return ($gb >= 1 ? rtrim(rtrim(number_format($gb, 2, '.', ''), '0'), '.') : '0') . ' GB';
	}

	public function subs($subs): Application|ResponseFactory|Response
	{
		$order = Order::with('configs', 'client')->where('subs', $subs)->firstOrFail();

		// ===== پایه‌ها
		$clientName  = $order->client->name ?? 'کاربر';
		$channelAt   = '@Satify_vpn';            // اگر از config می‌گیری، اینجا بخوان
		$channelUrl  = 't.me/Satify_vpn';
		$displayName = '⚡️ ' . $channelAt . ' | ' . $clientName;

		$links = $order->configs->map(fn($row) => trim($row->config))->filter()->values();

		$totalBytes = (int) max(0, ($order->traffic_gb ?? 0) * 1024 * 1024 * 1024);
		$usedBytes  = (int) max(0, ($order->used_traffic_gb ?? 0) * 1024 * 1024 * 1024);
		$leftBytes  = max(0, $totalBytes - $usedBytes);

		$expireTs  = $order->expires_at ? Carbon::parse($order->expires_at)->getTimestamp() : 0;
		$expireStr = $order->expires_at
			? (Carbon::parse($order->expires_at)->timezone(config('app.timezone', 'UTC'))->diff()->days . ' ' . tr_helper('contents', 'Days'))
			: '∞';

		// ===== نودهای اطلاع‌رسان (نمایشی)
		$infoNode1 = $this->vlessInfoNode("{$displayName} — کانال: {$channelUrl}");
		$infoNode2 = $this->vlessInfoNode("باقیمانده: " . $this->humanGB($leftBytes) . " از " . ($order->traffic_gb ?? 0) . " GB");
		$infoNode3 = $this->vlessInfoNode("• انقضا: {$expireStr}");

		$bodyPlain = collect([$infoNode1, $infoNode2, $infoNode3])->merge($links)->implode("\n");

		// ===== تشخیص حالت (وب‌ویو یا متن)
		$request = request();
		$ua      = Str::lower($request->userAgent() ?? '');
		$accept  = Str::lower($request->header('accept', ''));

		// پارامترهای اجباری اختیاری
		$forceWeb = $request->boolean('web');
		$forceRaw = $request->boolean('raw') || $request->has('base64'); // base64 یعنی متن

		// تشخیص کلاینت‌های اشتراک (heuristic)
		$isClientApp = (function(string $ua): bool {
			$tokens = [
				'v2rayng','v2rayn','v2box','hiddify','sing-box','singbox','nekobox',
				'shadowrocket','stash','quantumult','surge','loon',
				'clash','clash-verge','clashx','clash.meta',
				'okhttp','go-http-client','curl','wget'
			];
			return Str::contains($ua, $tokens);
		})($ua);

		$looksLikeBrowser = (function(string $ua): bool {
			$browserTokens = ['mozilla','chrome','safari','firefox','edg','opera','crios','fxios'];
			return Str::contains($ua, $browserTokens);
		})($ua);

		// تصمیم نهایی
		$wantsWeb = $forceWeb || (!$forceRaw && (!$isClientApp && (Str::contains($accept, 'text/html') || $looksLikeBrowser)));

		if ($wantsWeb) {
			$subscriptionUrl = route('shop.orders.subs', $order->subs); // یا روت خودت
			$expiresAtIso    = $order->expires_at ? Carbon::parse($order->expires_at)->toIso8601String() : null;
			$resetInterval   = $order->reset_interval ?? 'no_reset';
			$qrUrl           = $order->qr_path ? asset('storage/' . $order->qr_path) : null;
			return response()->view('shop::orders.subs', [
				'clientName'      => $clientName,
				'channelAt'       => $channelAt,
				'configs'         => $links->all(),
				'totalGB'         => (float) ($order->traffic_gb ?? 0),
				'usedGB'          => (float) ($order->used_traffic_gb ?? 0),
				'totalBytes'      => (int) max(0, ($order->traffic_gb ?? 0) * 1024 * 1024 * 1024),
				'usedBytes'       => (int) max(0, ($order->used_traffic_gb ?? 0) * 1024 * 1024 * 1024),
				'expiresAt'       => $order->expires_at ? Carbon::parse($order->expires_at)->toIso8601String() : null,
				'resetInterval'   => $order->reset_interval ?? 'no_reset',
				'subscriptionUrl' => route('shop.orders.subs', $order->subs),
				'title'           => $displayName,
				'qrUrl'           => $order->qr_path ? asset('storage/' . $order->qr_path) : null,
			], 200, [
				'Content-Type'  => 'text/html; charset=utf-8',
				'Cache-Control' => 'no-store, no-cache, must-revalidate',
			]);
		}

		// ===== خروجی متن سابسکریپشن (برای کلاینت‌ها)
		if ($request->query('base64')) {
			return response(base64_encode($bodyPlain), 200)
				->header('Content-Type', 'text/plain')
				->header('Cache-Control', 'no-store, no-cache, must-revalidate');
		}

		$profileTitleHeader = Str::contains($ua, 'hiddify')
			? 'base64:' . base64_encode($displayName)              // Hiddify: UTF-8 پشتیبانی
			: (Str::ascii($displayName) ?: ltrim($channelAt, '@')); // سایر کلاینت‌ها: ASCII امن

		return response($bodyPlain, 200)
			->header('Content-Type', 'text/plain; charset=utf-8')
			->header('Cache-Control', 'no-store, no-cache, must-revalidate')
			->header('Profile-Title', $profileTitleHeader)
			->header('Subscription-Userinfo', "upload=0; download={$usedBytes}; total={$totalBytes}; expire={$expireTs}");
	}
}
