<?php

namespace Modules\Client\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Modules\Logging\Traits\Loggable;
use Modules\Client\Models\Client;
use Modules\Client\Http\Requests\ClientRequest;
use Modules\Shop\Models\Product;

class ClientController extends Controller
{
	use Loggable;

	public function index(): Factory|Application|View
	{
		$clients = Client::paginate();
		return view('client::clients.list', compact('clients'));
	}

	public function create(): Factory|Application|View
	{
		$users = User::getActiveUsers();
		return view('client::clients.create', compact('users'));
	}

	public function store(ClientRequest $request): RedirectResponse
	{
		$fields = $request->validated();

		// Force type to 'panel' for panel-created clients
		$fields['type'] = 'panel';

		if (!isset($fields['user_id'])) {
			$fields['user_id'] = Auth::id();
		}

		$client = Client::query()->create($fields);

		$this->logInfo('createClient', 'Client created', [
			'client_id' => $client->id,
			'type' => $client->type,
		]);

		return redirect()->route('clients.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}

	public function edit(Client $client): Factory|Application|View
	{
		$users 		= User::getActiveUsers();
		$products 	= Product::getActiveProducts($client->user_id);
		return view('client::clients.edit', compact('client', 'users', 'products'));
	}

	public function update(ClientRequest $request, Client $client): RedirectResponse
	{
		$fields = $request->validated();

		// Prevent changing type from panel to telegram
		if ($client->type === 'panel') {
			$fields['type'] = 'panel';
		}

		$client->update($fields);

		$this->logInfo('updateClient', 'Client updated', [
			'client_id' => $client->id,
		]);

		return redirect()->route('clients.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyUpdated'));
	}

	public function destroy(Client $client): RedirectResponse
	{
		$client->delete();

		$this->logInfo('deleteClient', 'Client deleted', [
			'client_id' => $client->id,
		]);

		return redirect()->back()
			->with('success_msg', tr_helper('contents', 'SuccessfullyDeleted'));
	}

	// Optional: Show method if you need it
	public function show(Client $client): Factory|Application|View
	{
		return view('client::clients.show', compact('client'));
	}
}
