<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Finance\Models\Wallet;
use Modules\Finance\Services\WalletService;

class WalletController extends Controller
{
	public function __construct(private WalletService $service) {}

	public function show(Wallet $wallet)
	{
		$wallet->load('transactions');
		return response()->json($wallet);
	}

	public function store(Request $request)
	{
		$data = $request->validate([
			'owner_type' => ['required','string'],
			'owner_id'   => ['required','integer'],
			'currency'   => ['nullable','string','max:10'],
		]);

		$owner = $data['owner_type']::findOrFail($data['owner_id']);
		$wallet = $this->service->createWallet($owner, $data['currency'] ?? 'IRR');

		return response()->json($wallet, Response::HTTP_CREATED);
	}

	public function deposit(Request $request, Wallet $wallet)
	{
		$data = $request->validate([
			'amount_minor'   => ['required','integer','min:1'],
			'idempotency_key'=> ['nullable','string','max:128'],
			'meta'           => ['nullable','array'],
		]);
		$tx = $this->service->deposit($wallet, $data['amount_minor'], $data['idempotency_key'] ?? null, $data['meta'] ?? []);
		return response()->json($tx, 201);
	}

	public function withdraw(Request $request, Wallet $wallet)
	{
		$data = $request->validate([
			'amount_minor'   => ['required','integer','min:1'],
			'idempotency_key'=> ['nullable','string','max:128'],
			'meta'           => ['nullable','array'],
		]);
		$tx = $this->service->withdraw($wallet, $data['amount_minor'], $data['idempotency_key'] ?? null, $data['meta'] ?? []);
		return response()->json($tx, 201);
	}

	public function transfer(Request $request)
	{
		$data = $request->validate([
			'from_wallet_id' => ['required','integer','exists:wallets,id'],
			'to_wallet_id'   => ['required','integer','exists:wallets,id'],
			'amount_minor'   => ['required','integer','min:1'],
			'idempotency_key'=> ['nullable','string','max:128'],
			'meta'           => ['nullable','array'],
		]);
		[$out, $in] = $this->service->transfer(
			Wallet::findOrFail($data['from_wallet_id']),
			Wallet::findOrFail($data['to_wallet_id']),
			$data['amount_minor'],
			$data['idempotency_key'] ?? null,
			$data['meta'] ?? []
		);
		return response()->json(['out' => $out, 'in' => $in], 201);
	}
}
