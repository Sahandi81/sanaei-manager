<?php

namespace Modules\Finance\Services;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Auth;
use Modules\Finance\Models\Wallet;
use Modules\Finance\Models\WalletTransaction;
use Modules\Finance\Models\Transaction;
use Modules\Logging\Traits\Loggable;

class WalletService
{
	use Loggable;

	public function __construct(private DatabaseManager $db) {}

	public function createWallet($owner, string $currency = 'IRR'): Wallet
	{
		$wallet = Wallet::firstOrCreate(
			[
				'owner_type' => get_class($owner),
				'owner_id'   => $owner->getKey(),
				'currency'   => $currency,
			],
			[
				'balance_minor' => 0,
				'status'        => Wallet::STATUS_ACTIVE,
			]
		);

		// log: ساخت کیف
		$this->logInfo('walletCreate', 'Wallet created or fetched', [
			'wallet_id'  => $wallet->id,
			'owner_type' => get_class($owner),
			'owner_id'   => $owner->getKey(),
			'currency'   => $currency,
		]);

		return $wallet;
	}

	public function deposit(Wallet $wallet, int $amountMinor, ?string $idempotencyKey = null, array $meta = []): WalletTransaction
	{
		try {
			return $this->db->transaction(function () use ($wallet, $amountMinor, $idempotencyKey, $meta) {
				if ($idempotencyKey && ($existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first())) {
					// log: idem hit
					$this->logInfo('walletDepositIdem', 'Deposit idempotency hit', [
						'wallet_id'        => $wallet->id,
						'idempotency_key'  => $idempotencyKey,
						'tx_id'            => $existing->id,
					]);
					return $existing;
				}

				// قفل ردیف
				$locked = Wallet::lockForUpdate()->findOrFail($wallet->id);
				$locked->balance_minor += $amountMinor;
				$locked->save();

				$wtx = $locked->transactions()->create([
					'type'                   => WalletTransaction::TYPE_DEPOSIT,
					'amount_minor'           => $amountMinor,
					'running_balance_minor'  => $locked->balance_minor,
					'idempotency_key'        => $idempotencyKey,
					'meta'                   => $meta,
				]);

				// همسان‌سازی با جدول عمومی transactions
				Transaction::create([
					'user_id'     => Auth::id(),
					'client_id'   => $meta['client_id'] ?? null,
					'amount'      => $amountMinor,                 // فرض: واحد خرد
					'currency'    => $locked->currency,
					'status'      => 1,                            // اگر مپ دیگری داری بگو
					'type'        => $meta['type'] ?? 'panel',     // panel|telegram
					'item_type'   => get_class($wtx),
					'item_id'     => $wtx->id,
					'description' => $meta['description'] ?? 'Wallet deposit',
				]);

				// log: موفق
				$this->logInfo('walletDeposit', 'Wallet deposit succeeded', [
					'wallet_id'  => $locked->id,
					'tx_id'      => $wtx->id,
					'amount'     => $amountMinor,
					'balance'    => $locked->balance_minor,
				]);

				return $wtx;
			});
		} catch (\Throwable $e) {
			// log: خطا
			$this->logError('walletDepositFail', 'Wallet deposit failed', [
				'wallet_id' => $wallet->id ?? null,
				'amount'    => $amountMinor,
				'message'   => $e->getMessage(),
			]);
			throw $e;
		}
	}

	public function withdraw(Wallet $wallet, int $amountMinor, ?string $idempotencyKey = null, array $meta = []): WalletTransaction
	{
		try {
			return $this->db->transaction(function () use ($wallet, $amountMinor, $idempotencyKey, $meta) {
				if ($idempotencyKey && ($existing = WalletTransaction::where('idempotency_key', $idempotencyKey)->first())) {
					$this->logInfo('walletWithdrawIdem', 'Withdraw idempotency hit', [
						'wallet_id'        => $wallet->id,
						'idempotency_key'  => $idempotencyKey,
						'tx_id'            => $existing->id,
					]);
					return $existing;
				}

				$locked = Wallet::lockForUpdate()->findOrFail($wallet->id);
				if ($locked->balance_minor < $amountMinor) {
					$this->logError('walletWithdrawInsufficient', 'Insufficient wallet balance', [
						'wallet_id' => $locked->id,
						'amount'    => $amountMinor,
						'balance'   => $locked->balance_minor,
					]);
					throw new \RuntimeException('Insufficient wallet balance.');
				}

				$locked->balance_minor -= $amountMinor;
				$locked->save();

				$wtx = $locked->transactions()->create([
					'type'                   => WalletTransaction::TYPE_WITHDRAW,
					'amount_minor'           => $amountMinor,
					'running_balance_minor'  => $locked->balance_minor,
					'idempotency_key'        => $idempotencyKey,
					'meta'                   => $meta,
				]);

				Transaction::create([
					'user_id'     => Auth::id(),
					'client_id'   => $meta['client_id'] ?? null,
					'amount'      => -$amountMinor,
					'currency'    => $locked->currency,
					'status'      => 1,
					'type'        => $meta['type'] ?? 'panel',
					'item_type'   => get_class($wtx),
					'item_id'     => $wtx->id,
					'description' => $meta['description'] ?? 'Wallet withdraw',
				]);

				$this->logInfo('walletWithdraw', 'Wallet withdraw succeeded', [
					'wallet_id' => $locked->id,
					'tx_id'     => $wtx->id,
					'amount'    => $amountMinor,
					'balance'   => $locked->balance_minor,
				]);

				return $wtx;
			});
		} catch (\Throwable $e) {
			$this->logError('walletWithdrawFail', 'Wallet withdraw failed', [
				'wallet_id' => $wallet->id ?? null,
				'amount'    => $amountMinor,
				'message'   => $e->getMessage(),
			]);
			throw $e;
		}
	}

	public function transfer(Wallet $from, Wallet $to, int $amountMinor, ?string $idempotencyKey = null, array $meta = []): array
	{
		if ($from->currency !== $to->currency) {
			$this->logError('walletTransferCurrencyMismatch', 'Currency mismatch for transfer', [
				'from_wallet_id' => $from->id,
				'to_wallet_id'   => $to->id,
				'from_currency'  => $from->currency,
				'to_currency'    => $to->currency,
			]);
			throw new \InvalidArgumentException('Currency mismatch for transfer.');
		}

		try {
			[$out, $in] = [
				$this->withdraw($from, $amountMinor, $idempotencyKey ? $idempotencyKey.':out' : null, $meta),
				$this->deposit($to,  $amountMinor, $idempotencyKey ? $idempotencyKey.':in'  : null, $meta),
			];

			$this->logInfo('walletTransfer', 'Wallet transfer succeeded', [
				'from_wallet_id' => $from->id,
				'to_wallet_id'   => $to->id,
				'amount'         => $amountMinor,
				'out_tx_id'      => $out->id,
				'in_tx_id'       => $in->id,
			]);

			return [$out, $in];
		} catch (\Throwable $e) {
			$this->logError('walletTransferFail', 'Wallet transfer failed', [
				'from_wallet_id' => $from->id ?? null,
				'to_wallet_id'   => $to->id ?? null,
				'amount'         => $amountMinor,
				'message'        => $e->getMessage(),
			]);
			throw $e;
		}
	}
}
