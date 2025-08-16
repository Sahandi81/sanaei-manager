<?php

namespace Modules\Server\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Logging\Traits\Loggable;
use Modules\Server\Models\Inbound;
use Modules\Server\Models\Server;

class InboundController extends Controller
{
	use Loggable;

	public function toggle(Server $server, Inbound $inbound, Request $request): JsonResponse
	{
		try {
			$enable = filter_var($request->input('enable'), FILTER_VALIDATE_BOOLEAN);
			$inbound->update(['status' => $enable]);

			$this->logError('toggleInbound', 'Inbound toggled successfully', [
				'status' 		=> $enable,
				'inbound_id' 	=> $inbound->id,
				'server_id' 	=> $inbound->server_id,
			]);

			return response()->json([
				'success' => true,
				'message' => tr_helper('contents', $enable ? 'InboundEnabled' : 'InboundDisabled'),
			]);
		} catch (\Throwable $e) {
			$this->logError('toggleInbound', 'Failed to toggle inbound', [
				'error' 		=> $e->getMessage(),
				'inbound_id' 	=> $inbound->id,
				'server_id' 	=> $inbound->server_id,
			]);
			return response()->json([
				'success' => false,
				'message' => tr_helper('contents', 'InboundToggleFailed'),
			], 500);
		}
	}
}
