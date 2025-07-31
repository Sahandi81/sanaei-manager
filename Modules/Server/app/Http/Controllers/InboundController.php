<?php

namespace Modules\Server\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Server\Models\Inbound;
use Modules\Server\Models\Server;

class InboundController extends Controller
{
	public function toggle(Server $server, Inbound $inbound, Request $request): JsonResponse
	{
		try {
			$enable = filter_var($request->input('enable'), FILTER_VALIDATE_BOOLEAN);
			$inbound->update(['enable' => $enable]);

			$this->log('toggleInbound', 'Inbound toggled successfully', $server->id, [
				'inbound_id' => $inbound->id,
				'enabled' => $enable,
			]);

			return response()->json([
				'success' => true,
				'message' => tr_helper('contents', $enable ? 'InboundEnabled' : 'InboundDisabled'),
			]);
		} catch (\Throwable $e) {
			$this->log('toggleInbound', 'Failed to toggle inbound', $server->id, [
				'error' => $e->getMessage(),
				'inbound_id' => $inbound->id,
			]);

			return response()->json([
				'success' => false,
				'message' => tr_helper('contents', 'InboundToggleFailed'),
			], 500);
		}
	}
}
