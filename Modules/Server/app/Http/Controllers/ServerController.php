<?php

namespace Modules\Server\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Illuminate\Foundation\Application;
use App\Http\Controllers\Controller;
use Modules\Logging\Traits\Loggable;
use Modules\Server\Models\Inbound;
use Modules\Server\Models\Server;
use Modules\Server\Services\PanelFactory;
use Modules\Server\Http\Requests\ServerRequest;

class ServerController extends Controller
{
	use Loggable;

	public function index(): View|Factory|Application
	{
		$servers = Server::query()
			->visibleTo(Auth::user())
			->with('users:id,name')
			->latest()
			->paginate();

		return view('server::servers.list', compact('servers'));
	}

	public function create(): View|Factory|Application
	{
		if (Auth::user()->role_key === 'super_admin') {
			$users = User::getNonSuperAdmins()->pluck('name', 'id');
		} else {
			$users = User::where('id', Auth::id())->pluck('name', 'id');
		}

		return view('server::servers.create', compact('users'));
	}

	public function store(ServerRequest $request): RedirectResponse
	{
		$fields = $request->validated();

		// ستون قدیمی را نادیده بگیر (مهاجرت به many-to-many)
		unset($fields['user_id']);

		$fields['creator_id'] = Auth::id();
		$fields['api_url']    = rtrim($fields['api_url'], '/') . '/';
		$fields['password']   = Crypt::encryptString($fields['password']);

		$server = Server::query()->create($fields);
		$this->logInfo('store', 'Created new server', ['server_id' => $server->id]);

		$userIds = collect($request->input('user_ids', []))->map(fn($id) => (int) $id)->unique()->values();

		if (Auth::user()->role_key !== 'super_admin') {
			$userIds = collect([Auth::id()]);
		}

		if ($userIds->isEmpty()) {
			$userIds = collect([Auth::id()]);
		}

		$server->users()->sync($userIds);

		$testConnection = $this->handleConnection($server);
		$this->syncInbounds($server);

		if (!$testConnection['live'] && !$testConnection['login']) {
			return redirect()
				->route('servers.edit', $server->id)
				->with('error_msg', tr_helper('contents', 'ChangesSavedButCheckConnection'));
		}

		return redirect()
			->route('servers.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}

	public function edit(Server $server): View|Factory|Application
	{
		$users = User::getActiveUsers()->pluck('email', 'id');
		$server->loadMissing('users:id,name');
		$usersHasAccess = DB::table('server_user')->where('server_id', $server->id)->pluck('user_id')->toArray();
		return view('server::servers.edit', compact('users', 'server', 'usersHasAccess'));
	}

	public function update(ServerRequest $request, Server $server): RedirectResponse
	{
		$fields = $request->validated();

		unset($fields['user_id']);

		$fields['creator_id'] = Auth::id();
		$fields['api_url']    = rtrim($fields['api_url'], '/') . '/';

		if (!empty($fields['password'])) {
			$fields['password'] = Crypt::encryptString($fields['password']);
		} else {
			unset($fields['password']);
		}

		$server->update($fields);
		$this->logInfo('update', 'Updated server info', ['server_id' => $server->id]);

		$userIds = collect($request->input('user_ids', []))->map(fn($id) => (int) $id)->unique()->values();

		if (Auth::user()->role_key !== 'super_admin') {
			$userIds = collect([Auth::id()]);
		}
		if ($userIds->isEmpty()) {
			$userIds = collect([Auth::id()]);
		}

		$server->users()->sync($userIds);

		$testConnection = $this->handleConnection($server->refresh());
		$this->syncInbounds($server);

		if (!$testConnection['live'] && !$testConnection['login']) {
			return redirect()
				->back()
				->with('error_msg', tr_helper('contents', 'ChangesSavedButCheckConnection'));
		}

		return redirect()
			->route('servers.index')
			->with('success_msg', tr_helper('contents', 'SuccessfullyUpdated'));
	}

	public function destroy(Server $server): RedirectResponse
	{
		$server->delete();
		$this->logInfo('destroy', 'Deleted server', ['server_id' => $server->id]);

		return redirect()
			->back()
			->with('success_msg', tr_helper('contents', 'SuccessfullyDeleted'));
	}

	public function syncInbounds(Server $server): JsonResponse
	{
		try {
			$panel = PanelFactory::make($server);
			$inbounds = $panel->getInbounds();
			if (!$inbounds) {
				throw new \Exception(tr_helper('contents', 'ServerDownOrWrongDetails'));
			}

			foreach ($inbounds as $data) {
				Inbound::query()->updateOrCreate([
					'server_id'        => $server->id,
					'panel_inbound_id' => $data['id'],
				], [
					'port'     => $data['port'],
					'protocol' => $data['protocol'],
					'stream'   => optional(json_decode($data['streamSettings'] ?? '{}', true))['network'] ?? null,
					'up'       => $data['up'] ?? 0,
					'down'     => $data['down'] ?? 0,
					'total'    => $data['total'] ?? 0,
					'enable'   => $data['enable'] ?? true,
					'remark'   => $data['remark'] ?? null,
					'raw'      => $data,
				]);
			}

			$this->logInfo('syncInbounds', 'Successfully synced inbounds', [
				'inbound_count' => count($inbounds),
				'server_id'     => $server->id,
			]);

			return response()->json([
				'status' => true,
				'msg'    => tr_helper('contents', 'SyncSuccessfully'),
			], 200);
		} catch (\Throwable $e) {
			$this->logError('syncInbounds', 'Failed to sync inbounds', [
				'error'     => $e->getMessage(),
				'server_id' => $server->id,
			]);

			return response()->json([
				'status' => false,
				'msg'    => tr_helper('contents', 'InboundsSyncError'),
			], 400);
		}
	}

	public function testConnection(Server $server): JsonResponse
	{
		$result = $this->handleConnection($server);

		return response()->json([
			'live'          => $result['live'],
			'login'         => $result['login'],
			'msg'           => $this->getConnectionMessage($result['live'], $result['login']),
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

		$newStatus = $this->updateServerStatus($server, $live, $loggedIn);

		$this->logInfo('handleConnection', 'Checked panel connection', [
			'live'      => $live,
			'login'     => $loggedIn,
			'status'    => $newStatus,
			'server_id' => $server->id,
		]);

		return [
			'live'          => $live,
			'login'         => $loggedIn,
			'server_status' => $newStatus,
		];
	}

	private function updateServerStatus(Server $server, bool $live, bool $loggedIn): int
	{
		$current = $server->status;
		$new = match ($current) {
			0, 2   => $live && $loggedIn ? 1 : $current,
			1      => (!$live || !$loggedIn) ? 2 : $current,
			default => $current,
		};

		if ($new !== $current) {
			$server->update(['status' => $new]);
		}

		return $new;
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
}
