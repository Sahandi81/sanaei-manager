<?php

namespace Modules\Shop\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Logging\Traits\Loggable;
use Modules\Server\Models\Server;
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

		return redirect()->route('products.index')->with('success', 'Product created');
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
			$product->servers()->sync($request->input('servers'));
		}

		$this->logInfo('updateProduct', 'Product updated', [
			'product_id' => $product->id,
		]);

		return redirect()->route('products.index')->with('success', 'Product updated');
	}

	public function destroy(Product $product): RedirectResponse
	{
		$product->delete();
		$this->logInfo('destroy', 'Deleted product', ['product_id' => $product->id]);

		return redirect()->back()
			->with('success_msg', tr_helper('contents', 'SuccessfullyDeleted'));
	}
}
