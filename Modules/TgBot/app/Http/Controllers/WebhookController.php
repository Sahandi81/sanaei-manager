<?php

namespace Modules\TgBot\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Logging\Traits\Loggable;
use Modules\TgBot\Services\UpdateRouter;

class WebhookController extends Controller
{
	use Loggable;

	public function handle(string $webhook, Request $request, UpdateRouter $router)
	{
		$owner = User::where('telegram_webhook', $webhook)->first();
		if (!$owner) {
			$this->logError('webhookHandle', 'Invalid webhook key', ['webhook' => $webhook]);
			return response()->json(['error' => 'Invalid webhook'], 403);
		}

		$update = $request->all();
		$router->dispatch($owner, $update);

		return response()->json(['ok' => true]);
	}
}

