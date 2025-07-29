<?php

namespace Modules\Server\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Controller;
use Modules\Server\Models\Server;
use Modules\Server\Services\PanelFactory;
use Modules\Logging\Services\LoggingService;
use Modules\Server\Http\Requests\ServerRequest;

class ServerController extends Controller
{
	public function __construct(protected LoggingService $logger)
	{
	}

	public function index(): View|Factory|Application
	{
		$servers = Server::query()->latest()->paginate(2);
		return view('server::servers.list', compact('servers'));
	}

	public function create(): View|Factory|Application
	{
		$users = User::getNonSuperAdmins();
		return view('server::servers.create', compact('users'));
	}

	public function store(ServerRequest $request): RedirectResponse
	{
		$fields = $request->validated();
		$fields['creator_id'] = Auth::id();
		$fields['api_url'] = rtrim($fields['api_url'], '/') . '/';
		$fields['password'] = Crypt::encryptString($fields['password']);

		$server = Server::query()->create($fields);
		$this->log('store', 'Created new server', $server->id);

		$testConnection = $this->handleConnection($server);
		if (!$testConnection['live'] && !$testConnection['login']){
			return redirect()->back()->with('error_msg', tr_helper('contents', 'ChangesSavedButCheckConnection'));
		}


		return redirect()->route('servers.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}

	public function edit(Server $server): View|Factory|Application
	{
		$users = User::getNonSuperAdmins();
		return view('server::servers.edit', compact('users', 'server'));
	}

	public function update(ServerRequest $request, Server $server): RedirectResponse
	{
		$fields = $request->validated();
		$fields['creator_id'] = Auth::id();
		$fields['api_url'] = rtrim($fields['api_url'], '/') . '/';

		if (!empty($fields['password'])) {
			$fields['password'] = Crypt::encryptString($fields['password']);
		} else {
			unset($fields['password']);
		}

		$server->update($fields);
		$this->log('update', 'Updated server info', $server->id);

		$testConnection = $this->handleConnection($server->refresh());
		if (!$testConnection['live'] && !$testConnection['login']){
			return redirect()->back()->with('error_msg', tr_helper('contents', 'ChangesSavedButCheckConnection'));
		}


		return redirect()->route('servers.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyUpdated'));
	}


	public function syncInbounds(Server $server): JsonResponse
	{
		$panel = PanelFactory::make($server);

		$live = $panel->testConnection();
		$loggedIn = false;
		$inbounds = [];

		if ($live) {
			$token = $panel->login();
			if ($token) {
				$server->update(['api_key' => $token]);
				$loggedIn = true;

				// Get inbounds
				$inbounds = $panel->getInbounds();

				// TODO: ذخیره‌سازی در دیتابیس
				// e.g., Inbound::syncFromPanel($server, $inbounds);
			}
		}

		// وضعیت سرور رو همزمان به‌روز کن
		$this->updateServerStatus($server, $live, $loggedIn);

		$this->log('syncInbounds', 'Synced inbounds from panel', $server->id, [
			'live' => $live,
			'login' => $loggedIn,
			'inbounds_count' => count($inbounds),
		]);

		return response()->json([
			'success' => $live && $loggedIn,
			'live' => $live,
			'login' => $loggedIn,
			'inbounds' => $inbounds,
			'msg' => $live && $loggedIn
				? tr_helper('contents', 'InboundsSyncedSuccessfully')
				: tr_helper('contents', 'CouldNotSyncInbounds'),
		], $live && $loggedIn ? 200 : 400);
	}


















	public function destroy(Server $server): RedirectResponse
	{
		$server->delete();
		$this->log('destroy', 'Deleted server', $server->id);

		return redirect()->route('servers.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyDeleted'));
	}

////////////////////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////Private Methods/////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////

	public function testConnection(Server $server): JsonResponse
	{
		$result = $this->handleConnection($server);

		return response()->json([
			'live' 			=> $result['live'],
			'login' 		=> $result['login'],
			'msg' 			=> $this->getConnectionMessage($result['live'], $result['login']),
			'server_status' => $result['server_status'],
		], $result['live'] && $result['login'] ? 200 : 400);
	}



	private function handleConnection(Server $server): array
	{
		$panel = PanelFactory::make($server);
		$live = $panel->testConnection();
		$loggedIn = false;

		if ($live) {
			$token = $panel->login();
			if ($token) {
				$server->update(['api_key' => $token]);
				$loggedIn = true;
			}
		}

		$newServerStatus = $this->updateServerStatus($server, $live, $loggedIn);

		$this->log('handleConnection', 'Auto checked panel connection on create/update', $server->id, [
			'live' => $live,
			'login' => $loggedIn,
			'status' => $newServerStatus
		]);

		return [
			'live' => $live,
			'login' => $loggedIn,
			'server_status' => $newServerStatus,
		];
	}

	private function updateServerStatus(Server $server, bool $live, bool $loggedIn): int
	{
		$currentStatus = $server->status;
		$newStatus = match ($currentStatus) {
			0, 2 => $live && $loggedIn ? 1 : $currentStatus,
			1 => !$live || !$loggedIn ? 2 : $currentStatus,
			default => $currentStatus,
		};

		if ($newStatus !== $currentStatus) {
			$server->update(['status' => $newStatus]);
		}

		return $newStatus;
	}

	private function getConnectionMessage(bool $live, bool $loggedIn): string
	{
		if (!$live) {
			return tr_helper('contents', 'ServerDownOrWrongDetails');
		}

		return $loggedIn
			? tr_helper('contents', 'SuccessConnection')
			: tr_helper('contents', 'ServerUpButWrongUsernameAndPass');
	}

	protected function log(string $action, string $message, int $serverId, array $extra = []): void
	{
		$this->logger->logInfo('Server', $action, $message, array_merge([
			'server_id' => $serverId,
			'user_id' => Auth::id(),
		], $extra));
	}

}
