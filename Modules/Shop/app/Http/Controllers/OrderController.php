<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Client\Models\Client;
use Modules\Finance\Http\Controllers\TransactionController;
use Modules\Finance\Models\Transaction;
use Modules\Logging\Traits\Loggable;
use Modules\Shop\Http\Requests\OrderRequest;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\Product;

class OrderController extends Controller
{
	use Loggable;

	public function store(OrderRequest $request): RedirectResponse
	{
		$fields = $request->validated();

		$client = Client::query()->findOrFail($fields['client_id']);
		$product = Product::query()->findOrFail($fields['product_id']);

		if (!auth()->user()->role->is_admin && $client->user_id !== auth()->id()) {
			return redirect()->back()->with('error_msg', tr_helper('contents', 'YouAreNotAllowedToDoThisAction'));
		}

		$order = Order::query()->create([
			'user_id'           => auth()->id(),
			'client_id'          => $client->id,
			'product_id'         => $product->id,
			'price'              => $product->price,
			'traffic_gb'         => $product->traffic_gb,
			'duration_days'     => $product->duration_days,
			'expires_at'         => now()->addDays($product->duration_days),
			'status'            => 0
		]);

		$transaction = Transaction::query()->create([
			'user_id'           => auth()->id(),
			'client_id'         => $client->id,
			'amount'            => $product->price,
			'currency'          => 'IRR',
			'description'      => "Payment for product: {$product->name}",
			'status'            => Transaction::STATUS_PENDING,
			'type'              => Transaction::TYPE_PANEL,
			'item_type'        => Order::class,
			'item_id'          => $order->id,
		]);

		// Inline approve
		(new TransactionController())->approve($transaction);

		$this->logInfo('orderCreated', 'Order created with transaction', [
			'order_id' => $order->id,
			'product_id' => $product->id,
			'amount' => $product->price,
		]);

		return redirect()->back()->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}
}
