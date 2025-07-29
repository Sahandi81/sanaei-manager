<?php

namespace Modules\Server\Http\Panels\API;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Logging\Services\LoggingService;
use Modules\Server\Models\Server;
use Modules\Server\Services\SanaeiApiService;

class SanaeiController extends Controller
{
	protected LoggingService $logger;

	public function __construct(LoggingService $logger)
	{
		$this->logger = $logger;
	}

	protected function getService(Server $server): SanaeiApiService
	{
		return new SanaeiApiService($server);
	}

	public function testConnection($serverId): JsonResponse
	{
		$server = Server::query()->findOrFail($serverId);
		$service = $this->getService($server);

		$live = $service->testConnection();

		$this->logger->logInfo('Sanaei', 'testConnection', 'Connection tested', [
			'server_id' => $server->id,
			'live' => $live
		]);

		return response()->json(['live' => $live]);
	}

	public function login($serverId): JsonResponse
	{
		$server = Server::query()->findOrFail($serverId);
		$service = $this->getService($server);

		$token = $service->login('admin', 'your-password-here'); // پیشنهاد: پسورد رو تو جدول ذخیره کن یا از env بگیر

		if ($token) {
			$server->update(['api_key' => $token]);
			$this->logger->logInfo('Sanaei', 'login', 'Login successful', ['server_id' => $server->id]);
			return response()->json(['success' => true]);
		}

		return response()->json(['success' => false], 401);
	}

	public function getInbounds($serverId): JsonResponse
	{
		$server = Server::query()->findOrFail($serverId);
		$service = $this->getService($server);

		$data = $service->getInbounds();

		return response()->json($data);
	}

	public function disableInbound(Request $request, $serverId): JsonResponse
	{
		$server = Server::query()->findOrFail($serverId);
		$service = $this->getService($server);

		$success = $service->disableInbound($request->id);

		return response()->json(['success' => $success]);
	}

	public function rechargeInbound(Request $request, $serverId): JsonResponse
	{
		$server = Server::query()->findOrFail($serverId);
		$service = $this->getService($server);

		$success = $service->rechargeInbound($request->id, $request->days);

		return response()->json(['success' => $success]);
	}

	public function createUser(Request $request, $serverId): JsonResponse
	{
		$server = Server::query()->findOrFail($serverId);
		$service = $this->getService($server);

		$success = $service->createUser($request->all());

		return response()->json(['success' => $success]);
	}
}
