<?php

namespace Modules\Shop\Observers;

use Illuminate\Support\Str;
use Modules\Shop\Models\Order;

class OrderObserver
{
	/**
	 * Handle the OrderObserver "creating" event.
	 */
	public function creating(Order $order): void
	{
		do {
			$subs = Str::random(12);
		} while (Order::query()->where('subs', $subs)->exists());

		$order->subs = $subs;
	}
    /**
     * Handle the OrderObserver "created" event.
     */
    public function created(Order $order): void {}

    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void {}

    /**
     * Handle the Order "deleted" event.
     */
    public function deleted(Order $order): void {}

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void {}

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void {}
}
