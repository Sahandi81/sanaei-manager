<?php

namespace Modules\Server\Http\Controllers;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Server\Http\Requests\ServerRequest;
use Modules\Server\Models\Server;

class ServerController extends Controller
{
	public function index()
	{
		$servers = Server::query()->latest()->paginate(2);

		return view('server::servers.list', compact('servers'));
	}


	public function create(): Factory|Application|View
	{
		$users = User::query()
			->where('role_key', '!=', 'super_admin')
			->pluck('email', 'id')->toArray();
		return view('server::servers.create', compact('users'));
	}

	public function store(ServerRequest $request): RedirectResponse
	{
		$fields = $request->validated();
		$fields['creator_id'] = Auth::id();
		Server::query()->create($fields);

		return redirect()->route('servers.index')->with('success_msg', tr_helper('contents', 'SuccessfullyCreated'));
	}
}
