<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Client\Models\Client;
use Modules\Finance\Http\Controllers\TransactionController;
use Modules\Finance\Models\Transaction;
use Modules\Logging\Traits\Loggable;
use Modules\QrGenerator\Services\QrGeneratorService;
use Modules\Shop\Http\Requests\OrderRequest;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\Product;
use stdClass;


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

	public function subs($subs)
	{
		$order = Order::with('configs', 'client')->where('subs', $subs)->firstOrFail();

		// ===== پایه‌ها
		$clientName  = $order->client->name ?? 'کاربر';
		$channelAt   = $order->user->bot_id ??'@Satify_vpn';
		$channelUrl  = $order->user->channel_id ?? 't.me/Satify_vpn';
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

		// ===== تشخیص حالت
		$request = request();
		$ua      = Str::lower($request->userAgent() ?? '');
		$accept  = Str::lower($request->header('accept', ''));

		$forceWeb = $request->boolean('web');
		$forceRaw = $request->boolean('raw') || $request->has('base64');

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

		// 👇 جدید: تشخیص sing-box (با UA یا پارامتر ?singbox=1 برای اجبار)
		$isSingBox = Str::contains($ua, ['sing-box', 'singbox']) || $request->boolean('singbox');

		// ===== اگر sing-box بود: خروجی JSON به سبک sing-box
		if ($isSingBox) {
			// 1) مبدل لینک‌های VLESS → outbound های sing-box
			$buildOutbounds = function (Collection $links): array {
				$nodes = [];
				$tags  = [];

				foreach ($links as $i => $uri) {
					$nodes[] = self::vlessUriToSingBoxOutbound($uri, $tags);
				}

				// حداقل اگر چیزی parse نشد، خالی نمونیم
				$nodes = array_values(array_filter($nodes));

				// 2) selector و urltest بر اساس تگ‌ها
				$selector = [
					'type'     => 'selector',
					'tag'      => 'proxy',
					'outbounds'=> array_values(array_merge(['auto','direct'], $tags)),
				];
				$urltest = [
					'type'      => 'urltest',
					'tag'       => 'auto',
					'interval'  => '10m',
					'tolerance' => 50,
					'url'       => 'http://www.gstatic.com/generate_204',
					'outbounds' => $tags ?: [],
				];
				$direct = ['type' => 'direct', 'tag' => 'direct'];

				return array_merge([$selector, $urltest, $direct], $nodes);
			};

			$config = [
				'dns' => [
					'final' => 'local-dns',
					'rules' => [
						['action'=>'route','clash_mode'=>'Global','server'=>'proxy-dns','source_ip_cidr'=>['172.19.0.0/30','fdfe:dcba:9876::1/126']],
						['action'=>'route','server'=>'proxy-dns','source_ip_cidr'=>['172.19.0.0/30','fdfe:dcba:9876::1/126']],
						['action'=>'route','clash_mode'=>'Direct','server'=>'direct-dns'],
						['action'=>'route','rule_set'=>['geosite-ir'],'server'=>'direct-dns'],
					],
					'servers' => [
						['address'=>'tcp://8.8.8.8','address_resolver'=>'local-dns','detour'=>'proxy','tag'=>'proxy-dns'],
						['address'=>'local','detour'=>'direct','tag'=>'local-dns'],
						['address'=>'tcp://8.8.8.8','detour'=>'direct','tag'=>'direct-dns'],
					],
					'strategy' => 'prefer_ipv4',
				],
				'inbounds' => [
					[
						'address' => ['172.19.0.1/30','fdfe:dcba:9876::1/126'],
						'auto_route' => true,
						'endpoint_independent_nat' => false,
						'mtu' => 9000,
						'platform' => ['http_proxy' => ['enabled'=>true,'server'=>'127.0.0.1','server_port'=>2080]],
						'stack' => 'system',
						'strict_route' => false,
						'type' => 'tun',
					],
					['listen'=>'127.0.0.1','listen_port'=>2080,'type'=>'mixed','users'=>[]],
				],
				'outbounds' => $buildOutbounds($links),
				'route' => [
					'auto_detect_interface' => true,
					'final' => 'proxy',
					'rule_set' => [
						['download_detour'=>'direct','format'=>'binary','tag'=>'geosite-private','type'=>'remote','url'=>'https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geosite/private.srs'],
						['download_detour'=>'direct','format'=>'binary','tag'=>'geosite-ir','type'=>'remote','url'=>'https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geosite/category-ir.srs'],
						['download_detour'=>'direct','format'=>'binary','tag'=>'geoip-private','type'=>'remote','url'=>'https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geoip/private.srs'],
						['download_detour'=>'direct','format'=>'binary','tag'=>'geoip-ir','type'=>'remote','url'=>'https://testingcf.jsdelivr.net/gh/MetaCubeX/meta-rules-dat@sing/geo/geoip/ir.srs'],
					],
					'rules' => [
						['action'=>'sniff'],
						['action'=>'route','clash_mode'=>'Direct','outbound'=>'direct'],
						['action'=>'route','clash_mode'=>'Global','outbound'=>'proxy'],
						['action'=>'hijack-dns','protocol'=>'dns'],
						['action'=>'route','outbound'=>'direct','rule_set'=>['geoip-private','geosite-private','geosite-ir','geoip-ir']],
					],
				],
			];

			// هدر حجم/انقضا برای سازگاری
			return response()->json($config, 200, [
				'Cache-Control' => 'no-store, no-cache, must-revalidate',
				'Profile-Title' => (Str::ascii($displayName) ?: ltrim($channelAt, '@')),
				'Subscription-Userinfo' => "upload=0; download={$usedBytes}; total={$totalBytes}; expire={$expireTs}",
				'Content-Disposition' => 'inline; filename="singbox-subscription.json"',
			]);
		}

		// ===== وب‌ویو
		$wantsWeb = $forceWeb || (!$forceRaw && (!$isClientApp && (Str::contains($accept, 'text/html') || $looksLikeBrowser)));
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

		// ===== متن (سایر کلاینت‌ها)
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

	/**
	 * لینک vless:// را به outbound sing-box تبدیل می‌کند.
	 * - WS / gRPC
	 * - TLS / Reality (pbk/sid) + uTLS (fp)
	 */
	protected static function vlessUriToSingBoxOutbound(string $uri, array &$tagCollector): ?array
	{
		$parts = parse_url($uri);
		if (!is_array($parts) || !isset($parts['scheme']) || strtolower($parts['scheme']) !== 'vless') {
			return null;
		}

		$uuid  = $parts['user'] ?? null;
		$host  = $parts['host'] ?? null;
		$port  = isset($parts['port']) ? (int)$parts['port'] : null;
		$frag  = isset($parts['fragment']) ? urldecode($parts['fragment']) : null;

		parse_str($parts['query'] ?? '', $q);

		$type     = strtolower($q['type'] ?? 'tcp');           // ws | grpc | (tcp default)
		$security = strtolower($q['security'] ?? 'none');      // none | tls | reality
		$path     = isset($q['path']) ? urldecode($q['path']) : null;
		$sni      = $q['sni'] ?? null;
		$alpn     = isset($q['alpn']) ? array_map('trim', explode(',', $q['alpn'])) : null;

		// Reality
		$pbk      = $q['pbk'] ?? null;
		$sid      = $q['sid'] ?? null;

		// gRPC
		$mode     = $q['mode'] ?? null;
		$service  = $q['serviceName'] ?? $mode ?? 'gun';

		// uTLS
		$fp       = $q['fp'] ?? null;

		// Tag
		$baseTag = $frag ? Str::slug($frag, '-') : ($host ? 'vless-' . Str::slug($host, '-') : 'vless-node');
		$tag     = $baseTag;
		static $used = [];
		$cnt = ($used[$tag] ?? 0) + 1; $used[$tag] = $cnt;
		if ($cnt > 1) $tag .= "-{$cnt}";
		$tagCollector[] = $tag;

		// TLS
		$tls = ['enabled' => $security !== 'none'];
		if ($tls['enabled']) {
			if ($sni)  { $tls['server_name'] = $sni; }
			if ($alpn) { $tls['alpn'] = $alpn; }
			if ($security === 'reality') {
				$tls['reality'] = [
					'enabled'    => true,
					'public_key' => (string) $pbk,
					'short_id'   => (string) ($sid ?? ''),
				];
				if ($fp) {
					$tls['utls'] = ['enabled' => true, 'fingerprint' => $fp];
				}
			} elseif ($fp) {
				// اگر TLS عادی و fp داشتیم، باز هم uTLS رو ست کن
				$tls['utls'] = ['enabled' => true, 'fingerprint' => $fp];
			}
		}

		// Transport: فقط برای WS و gRPC تنظیم می‌کنیم.
		// برای TCP ساده یا هر نوع ناشناخته، اصلاً transport نذار.
		$transport = null;
		if ($type === 'ws') {
			$transport = [
				'type' => 'ws',
				'path' => $path ?: '/',
			];
			// Host header فقط اگر SNI داشتیم
			if ($sni) {
				$transport['headers'] = ['Host' => $sni];
			}
		} elseif ($type === 'grpc') {
			$transport = [
				'type' => 'grpc',
				'service_name' => $service ?: 'gun',
			];
		}

		// پورت پیش‌فرض
		if (!$port) {
			$port = ($security !== 'none') ? 443 : 80;
		}

		$out = [
			'type'        => 'vless',
			'tag'         => $tag,
			'server'      => $host,
			'server_port' => $port,
			'uuid'        => $uuid,
			'tls'         => $tls,
		];
		if (!empty($transport)) {
			$out['transport'] = $transport;
		}

		return $out;
	}

}
