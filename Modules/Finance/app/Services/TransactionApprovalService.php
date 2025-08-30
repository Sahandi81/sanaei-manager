<?php

namespace Modules\Finance\Services;

use Modules\Finance\Models\Transaction;
use Modules\Shop\Models\Order;
use Modules\Shop\Services\OrderActivationService;
use Modules\Shop\Services\OrderQrService;

class TransactionApprovalService
{
	public function __construct(
		protected OrderActivationService $activation,
		protected OrderQrService $qr
	) {}

	/**
	 * Approve transaction and activate related order if exists.
	 * Returns [$orderOrNull, $qrRelativeOrNull].
	 */
	public function approve(Transaction $tx, int $approvedByUserId): array
	{
		if ($tx->status === Transaction::STATUS_APPROVED) {
			return [$this->resolveOrder($tx), $this->existingQr($tx)];
		}

		$tx->update([
			'status'      => Transaction::STATUS_APPROVED,
			'verified_at' => now(),
			'modified_by' => $approvedByUserId,
		]);

		$order = $this->resolveOrder($tx);
		$qrRel = null;

		if ($order) {
			$this->activation->activateOrder($order);
			$qrRel = $this->qr->ensure($order);
		}

		return [$order, $qrRel];
	}

	protected function resolveOrder(Transaction $tx): ?Order
	{
		if ($tx->item_type === Order::class && $tx->item_id) {
			return Order::find($tx->item_id);
		}
		return null;
	}

	protected function existingQr(Transaction $tx): ?string
	{
		$order = $this->resolveOrder($tx);
		return $order?->qr_path ?: null;
	}
}
