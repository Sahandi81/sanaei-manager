<?php

namespace Modules\Shop\Services;

use Modules\Shop\Models\Order;

class OrderActivationService
{
	public function activateOrder(Order $order): void
	{
		$order->update(['status' => Order::STATUS_ACTIVE]);

		// اعمال تغییرات دیگر مربوط به فعال‌سازی سفارش
		// مثلا: ارسال نوتیفیکیشن، کاهش موجودی محصول و...
	}
}
