<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Logging\Traits\Loggable;
use Modules\Server\Models\Server;
use Modules\Server\Services\SyncUserService;
use Modules\Shop\Models\Order;
use Modules\Shop\Models\OrderConfig;
use Modules\Shop\Models\Product;
use Modules\Shop\Http\Requests\ProductRequest;

class ProductController extends Controller
{
	use Loggable;

	public function index(): Factory|Application|View
	{
		$products = Product::paginate();

		return view('shop::products.list', compact('products'));
	}

	public function create(): Factory|Application|View
	{
		$servers = Server::getActiveServers();
		$products = Product::getActiveProducts();
		$users = User::getActiveUsers();

		return view('shop::products.create', compact('servers', 'products', 'users'));
	}

	public function store(ProductRequest $request): RedirectResponse
	{
		$fields = $request->validated();
		$product = Product::query()->create($fields);

		if ($request->has('servers')) {
			$product->servers()->sync($request->input('servers'));
		}

		if (!$fields['user_id']) {
			$fields['user_id'] = Auth::id();
		}

		$this->logInfo('createProduct', 'Product created', [
			'product_id' => $product->id,
			'is_test' => $product->is_test,
		]);

		return redirect()->route('shop.products.index')->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}

	public function edit(Product $product): Factory|Application|View
	{
		$servers = Server::getActiveServers();
		$products = Product::getActiveProducts();
		$users = User::getActiveUsers();

		return view('shop::products.edit', compact('servers', 'products', 'users', 'product'));
	}

	public function update(ProductRequest $request, Product $product): RedirectResponse
	{
		$fields = $request->validated();
		$product->update($fields);

		if ($request->has('servers')) {
			$originalServerIds = $product->servers()->pluck('servers.id')->toArray();

			$product->servers()->sync($request->input('servers'));

			$removedServerIds = array_diff($originalServerIds, $request->input('servers'));
			if (!empty($removedServerIds)) {
				OrderConfig::query()->whereHas('order', function($query) use ($product) {
					$query->where('product_id', $product->id);
				})
					->whereIn('server_id', $removedServerIds)
					->delete();
			}
		}

		$this->logInfo('updateProduct', 'Product updated', [
			'product_id' => $product->id,
		]);

		return redirect()->route('shop.products.index')->with('success_msg',  tr_helper('contents', 'SuccessfullyUpdated'));
	}

	public function syncConfigs(Product $product): JsonResponse
	{
		try{
			$res = (new SyncUserService())->syncUsersByProduct($product);
			if (!$res)
				throw new \Exception('syncUsersByProduct return false, check system logs');
			return response()->json([
			'success' => true,
			'msg' => tr_helper('contents', 'SuccessfullySynced'),
				]);
		} catch (\Throwable $e) {

			$this->logError('syncConfigs', 'Failed to sync product configs', [
			'error' 		=> $e->getMessage(),
			'product_id' 	=> $product->id,
			]);
			return response()->json([
			'success' => false,
			'msg' => tr_helper('contents', 'FailedToSync'),
			], 500);
		}
	}

	public function destroy(Product $product): RedirectResponse
	{
		$product->delete();
		$this->logInfo('destroy', 'Deleted product', ['product_id' => $product->id]);

		return redirect()->back()
			->with('success_msg', tr_helper('contents', 'SuccessfullyDeleted'));
	}
}
