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
use Symfony\Component\Yaml\Yaml;


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
		$channelAt   = '@Satify_vpn';
		$channelUrl  = 't.me/Satify_vpn';
		$displayName = '⚡️ ' . $channelAt . ' | ' . $clientName;

		$links = $order->configs->map(fn($row) => trim($row->config))->filter()->values();

		$totalBytes = (int) max(0, ($order->traffic_gb ?? 0) * 1024 * 1024 * 1024);
		$usedBytes  = (int) max(0, ($order->used_traffic_gb ?? 0) * 1024 * 1024 * 1024);
		$expireTs   = $order->expires_at ? Carbon::parse($order->expires_at)->getTimestamp() : 0;

		// ===== نودهای اطلاع‌رسان (نمایشی) فقط برای کلاینت‌های v2
		$infoNode1 = $this->vlessInfoNode("{$displayName} — کانال: {$channelUrl}");
		$infoNode2 = $this->vlessInfoNode("باقیمانده: " . $this->humanGB(max(0, $totalBytes - $usedBytes)) . " از " . ($order->traffic_gb ?? 0) . " GB");
		$infoNode3 = $this->vlessInfoNode("• انقضا: " . ($order->expires_at
				? (Carbon::parse($order->expires_at)->timezone(config('app.timezone', 'UTC'))->diff()->days . ' ' . tr_helper('contents', 'Days'))
				: '∞'));

		$bodyPlain = collect([$infoNode1, $infoNode2, $infoNode3])->merge($links)->implode("\n");

		// ===== تشخیص حالت
		$request = request();
		$ua      = Str::lower($request->userAgent() ?? '');
		$accept  = Str::lower($request->header('accept', ''));

		$forceWeb   = $request->boolean('web');
		$forceRaw   = $request->boolean('raw') || $request->has('base64');
		$wantClash  = $request->boolean('clash') || $request->query('format') === 'clash'
			|| Str::contains($accept, ['application/yaml','text/yaml'])
			|| Str::contains($ua, ['clash','clash-verge','clash.meta','clashx']);

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

		$wantsWeb = !$wantClash && ($forceWeb || (!$forceRaw && (!$isClientApp && (Str::contains($accept, 'text/html') || $looksLikeBrowser))));

		if ($wantsWeb) {
			return response()->view('shop::orders.subs', [
				'clientName'      => $clientName,
				'channelAt'       => $channelAt,
				'configs'         => $links->all(),
				'totalGB'         => (float) ($order->traffic_gb ?? 0),
				'usedGB'          => (float) ($order->used_traffic_gb ?? 0),
				'totalBytes'      => $totalBytes,
				'usedBytes'       => $usedBytes,
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

		// ===== خروجی Clash YAML
		if ($wantClash) {
			$proxies = [];
			foreach ($links as $idx => $link) {
				$parsed = $this->linkToClashProxy($link, $idx + 1, $displayName);
				if ($parsed) $proxies[] = $parsed;
			}

			// اگر هیچ پراکسی parse نشد، خطا نده: YAML حداقلی
			$proxyNames = array_map(fn($p) => $p['name'] ?? ('Proxy-'.$p['server'] ?? 'P'), $proxies);

			$yaml = [
				// گزینه‌های پایه
				'mixed-port' => 7890,
				'allow-lan'  => true,
				'mode'       => 'rule',
				'log-level'  => 'info',
				'ipv6'       => false,

				// هدر اطلاعاتی برای بعضی کلاینت‌ها (غیراستاندارد، ولی بدرد مانیتورینگ می‌خوره)
				'profile' => [
					'store-selected' => true,
					'store-fake-ip'  => true,
				],

				// لیست پراکسی‌ها
				'proxies' => $proxies,

				// گروه‌ها
				'proxy-groups' => [
					[
						'name' => '♻️ Auto',
						'type' => 'url-test',
						'url'  => 'http://www.gstatic.com/generate_204',
						'interval' => 300,
						'tolerance'=> 50,
						'proxies' => $proxyNames,
					],
					[
						'name'   => '🔰 Select',
						'type'   => 'select',
						'proxies'=> array_merge(['♻️ Auto'], $proxyNames),
					],
					[
						'name'   => '🌍 Direct',
						'type'   => 'select',
						'proxies'=> ['DIRECT','♻️ Auto','🔰 Select'],
					],
					[
						'name'   => '🛡️ Block',
						'type'   => 'select',
						'proxies'=> ['REJECT','DIRECT'],
					],
				],

				// قواعد ساده (می‌تونی با لیست‌ها/دومن‌لیست‌ها جایگزین کنی)
				'rules' => [
					'DOMAIN,clash.razord.top,DIRECT',
					'DOMAIN,yacd.haishan.me,DIRECT',
					'GEOIP,IR,DIRECT',
					'MATCH,🔰 Select',
				],
			];

			$out = Yaml::dump($yaml, 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
			return response($out, 200)
				->header('Content-Type', 'application/yaml; charset=utf-8')
				->header('Cache-Control', 'no-store, no-cache, must-revalidate')
				->header('Subscription-Userinfo', "upload=0; download={$usedBytes}; total={$totalBytes}; expire={$expireTs}");
		}

		// ===== خروجی متن برای کلاینت‌های v2ray/sing-box و ...
		if ($request->query('base64')) {
			return response(base64_encode($bodyPlain), 200)
				->header('Content-Type', 'text/plain')
				->header('Cache-Control', 'no-store, no-cache, must-revalidate');
		}

		$profileTitleHeader = Str::contains($ua, 'hiddify')
			? 'base64:' . base64_encode($displayName)
			: (Str::ascii($displayName) ?: ltrim($channelAt, '@'));

		return response($bodyPlain, 200)
			->header('Content-Type', 'text/plain; charset=utf-8')
			->header('Cache-Control', 'no-store, no-cache, must-revalidate')
			->header('Profile-Title', $profileTitleHeader)
			->header('Subscription-Userinfo', "upload=0; download={$usedBytes}; total={$totalBytes}; expire={$expireTs}");
	}
	private function linkToClashProxy(string $link, int $seq, string $fallbackName = 'Node'): ?array
	{
		try {
			$u = trim($link);

			if (Str::startsWith($u, 'vmess://')) {
				// vmess => base64(json)
				$payload = base64_decode(substr($u, 8), true);
				if (!$payload) return null;
				$j = json_decode($payload, true);
				if (!is_array($j)) return null;

				$name = $j['ps'] ?? "VMESS-{$seq}";
				return [
					'type'   => 'vmess',
					'name'   => $name,
					'server' => $j['add'] ?? '0.0.0.0',
					'port'   => (int)($j['port'] ?? 443),
					'uuid'   => $j['id'] ?? '',
					'alterId'=> (int)($j['aid'] ?? 0),
					'cipher' => 'auto',
					'tls'    => ($j['tls'] ?? '') === 'tls',
					'servername' => $j['sni'] ?? null,
					'network'=> $j['net'] ?? 'tcp',
					'ws-opts'=> ($j['net'] ?? '') === 'ws' ? [
						'path' => $j['path'] ?? '/',
						'headers' => array_filter([
							'Host' => $j['host'] ?? null,
						]),
					] : null,
				];
			}

			if (Str::startsWith($u, 'vless://')) {
				// vless://<uuid>@host:port?param=...#name
				$parts = parse_url($u);
				if (!$parts || empty($parts['user']) || empty($parts['host']) || empty($parts['port'])) return null;

				parse_str($parts['query'] ?? '', $q);
				$name = isset($parts['fragment']) ? urldecode($parts['fragment']) : "VLESS-{$seq}";

				$flow = $q['flow'] ?? null; // xtls-rprx-vision برای reality
				$security = $q['security'] ?? '';
				$sni = $q['sni'] ?? $q['host'] ?? null;

				$network = $q['type'] ?? $q['network'] ?? 'tcp';
				$grpcServiceName = $q['serviceName'] ?? null;

				$obj = [
					'type'   => 'vless',
					'name'   => $name,
					'server' => $parts['host'],
					'port'   => (int)$parts['port'],
					'uuid'   => $parts['user'],
					'udp'    => true,
					'tls'    => in_array($security, ['tls','reality'], true),
					'servername' => $sni,
					'flow'   => $flow,
					'network'=> $network,
				];

				if ($network === 'ws') {
					$obj['ws-opts'] = [
						'path' => $q['path'] ?? '/',
						'headers' => array_filter([
							'Host' => $q['host'] ?? $sni ?? null,
						]),
					];
				} elseif ($network === 'grpc') {
					$obj['grpc-opts'] = [
						'grpc-service-name' => $grpcServiceName ?? 'grpc',
					];
				} elseif ($network === 'tcp' && ($q['headerType'] ?? '') === 'http') {
					$obj['http-opts'] = [
						'method' => 'GET',
						'path'   => [$q['path'] ?? '/'],
						'headers'=> array_filter([
							'Host' => $q['host'] ?? $sni ?? null,
						]),
					];
					$obj['network'] = 'http'; // در Meta، tcp+http با http تعریف می‌شود
				}

				if (($security ?? '') === 'reality') {
					// پارامترهای Reality
					if (!empty($q['pbk'])) $obj['reality-opts']['public-key'] = $q['pbk'];
					if (!empty($q['sid'])) $obj['reality-opts']['short-id']   = $q['sid'];
				}

				return $obj;
			}

			if (Str::startsWith($u, 'trojan://')) {
				// trojan://password@host:port?peer=sni&security=tls&type=ws&path=/#name
				$parts = parse_url($u);
				if (!$parts || empty($parts['user']) || empty($parts['host']) || empty($parts['port'])) return null;
				parse_str($parts['query'] ?? '', $q);
				$name = isset($parts['fragment']) ? urldecode($parts['fragment']) : "TROJAN-{$seq}";

				$network = $q['type'] ?? 'tcp';
				$obj = [
					'type'   => 'trojan',
					'name'   => $name,
					'server' => $parts['host'],
					'port'   => (int)$parts['port'],
					'password' => $parts['user'],
					'sni'    => $q['sni'] ?? $q['peer'] ?? null,
					'udp'    => true,
					'network'=> $network,
				];

				if ($network === 'ws') {
					$obj['ws-opts'] = [
						'path' => $q['path'] ?? '/',
						'headers' => array_filter([
							'Host' => $q['host'] ?? ($q['sni'] ?? null),
						]),
					];
				} elseif ($network === 'grpc') {
					$obj['grpc-opts'] = [
						'grpc-service-name' => $q['serviceName'] ?? 'grpc',
					];
				}

				return $obj;
			}

			if (Str::startsWith($u, 'ss://')) {
				// ss://base64(method:password)@host:port#name  یا  ss://method:password@host:port#name
				$raw = substr($u, 5);
				$name = 'SS-'.$seq;

				// جداسازی فرگمنت
				if (str_contains($raw, '#')) {
					[$raw, $frag] = explode('#', $raw, 2);
					$name = urldecode($frag) ?: $name;
				}

				if (str_contains($raw, '@')) {
					// حالت غیر base64
					[$cred, $addr] = explode('@', $raw, 2);
					[$method, $password] = explode(':', $cred, 2);
					[$host, $port] = explode(':', $addr, 2);
				} else {
					// حالت base64
					$dec = base64_decode($raw, true);
					if (!$dec || !str_contains($dec, '@')) return null;
					[$cred, $addr] = explode('@', $dec, 2);
					[$method, $password] = explode(':', $cred, 2);
					[$host, $port] = explode(':', $addr, 2);
				}

				return [
					'type'   => 'ss',
					'name'   => $name,
					'server' => $host,
					'port'   => (int)$port,
					'cipher' => $method,
					'password' => $password,
					'udp'    => true,
				];
			}

			return null;
		} catch (\Throwable $e) {
			// لاگ کن که بعداً دیباگ کنی
			\Log::warning('Subs link parse failed', ['e' => $e->getMessage(), 'link' => $link]);
			return null;
		}
	}
}
