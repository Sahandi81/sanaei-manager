<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Client\Models\Client;
use Modules\FileManager\Facades\FileManager;
use Modules\Finance\Models\Transaction;
use Modules\Finance\Http\Requests\TransactionRequest;
use Modules\Logging\Traits\Loggable;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\OrderActivationService;

class TransactionController extends Controller
{
	use Loggable;

	public function index(): Factory|Application|View
	{
		$transactions = Transaction::paginate();
		return view('finance::transactions.list', compact('transactions'));
	}

	public function create(): Factory|Application|View
	{
		$clients = Client::all();
		$users = User::all();
		return view('finance::transactions.create', compact('clients', 'users'));
	}

	public function store(TransactionRequest $request): RedirectResponse
	{
		$fields = $request->validated();
		$fields['user_id'] = Auth::id();
		$fields['status'] = Transaction::STATUS_PENDING;
		$transaction = Transaction::query()->create($fields);

		if ($request->hasFile('files')) {
			FileManager::store(
				$transaction,
				$request->file('files'),
				'transactions/'.$transaction->id
			);
		}

		$this->logInfo('createTransaction', 'Transaction created', [
			'transaction_id' => $transaction->id,
			'amount' => $transaction->amount,
		]);

		return redirect()->route('finance.transactions.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}


	public function edit(Transaction $transaction): Factory|Application|View
	{
		$clients = Client::all();
		$users = User::all();
		return view('finance::transactions.edit', compact('transaction', 'clients', 'users'));
	}

	public function update(TransactionRequest $request, Transaction $transaction): RedirectResponse
	{
		$fields = $request->validated();

		if ($transaction->status !== Transaction::STATUS_PENDING){
			return redirect()->route('finance.transactions.index')
				->with('success_msg', tr_helper('contents', 'CannotUpdateUpdatedValue'));
		}

		if ($request->has('status') && $fields['status'] == Transaction::STATUS_APPROVED) {
			$this->approve($transaction);
		}

		$transaction->update($fields);

		if ($request->hasFile('files')) {
			FileManager::store(
				$transaction,
				$request->file('files'),
				'transactions/'.$transaction->id
			);
		}

		$this->logInfo('updateTransaction', 'Transaction updated', [
			'transaction_id' => $transaction->id,
			'status' => $transaction->status,
		]);

		return redirect()->route('finance.transactions.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyUpdated'));
	}

	public function approve(Transaction $transaction): bool
	{
		if ($transaction->status !== Transaction::STATUS_PENDING) {
			return false; // Just pending transactions
		}

		DB::transaction(function() use ($transaction) {
			$transaction->update([
				'status' => Transaction::STATUS_APPROVED,
				'verified_at' => now(),
				'modified_by' => Auth::id(),
			]);

			if ($transaction->item_type === Order::class) {
				$order = Order::find($transaction->item_id);
				if ($order) {
					(new OrderActivationService())->activateOrder($order);
				}
			}

			$this->logInfo('transactionApproved', 'Transaction approved and item activated', [
				'transaction_id' 	=> $transaction->id,
				'item_id'	 		=> $transaction->item_id ?? null,
				'item_type'	 		=> $transaction->item_type ?? null,
			]);
		});
		return true;
	}
	public function destroy(Transaction $transaction): RedirectResponse
	{
		$transaction->delete();

		$this->logInfo('deleteTransaction', 'Transaction deleted', [
			'transaction_id' => $transaction->id,
		]);

		return redirect()->back()
			->with('success_msg', tr_helper('contents', 'SuccessfullyDeleted'));
	}
}
