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

	public function subs($subs): Application|Response|ResponseFactory
	{
		$order = Order::with('configs')->where('subs', $subs)->firstOrFail();

		$title = 'Sahandi81';

		$links = $order->configs->map(function ($row) {
			$cfg = trim($row->config);
			return $cfg;
		})->filter()->values();

		$bodyPlain = $links->implode("\n");

		$totalBytes = (int) max(0, ($order->traffic_gb ?? 0) * 1024 * 1024 * 1024);
		$usedBytes  = (int) max(0, ($order->used_traffic_gb ?? 0) * 1024 * 1024 * 1024);
		$expireTs   = $order->expires_at ? Carbon::parse($order->expires_at)->getTimestamp() : 0;

		if (request()->query('base64')) {
			$payload = base64_encode($bodyPlain);
			return response($payload, 200)
				->header('Content-Type', 'text/plain') // بعضی کلاینت‌ها هم application/octet-stream می‌خوان
				->header('Cache-Control', 'no-store, no-cache, must-revalidate');
		}

		// برگردوندن متن ساده + هدرهای مفید
		return response($bodyPlain, 200)
			->header('Content-Type', 'text/plain; charset=utf-8')
			->header('Cache-Control', 'no-store, no-cache, must-revalidate')
			->header('Profile-Title', $title) // بعضی کلاینت‌ها این رو می‌خوان
			->header('Subscription-Userinfo', "upload=0; download={$usedBytes}; total={$totalBytes}; expire={$expireTs}");
	}
}
