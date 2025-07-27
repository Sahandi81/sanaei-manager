<?php

namespace Modules\Permission\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\Permission\App\Http\Requests\RoleRequest;
use Modules\Permission\App\Models\Role;

class RoleController extends Controller
{

	public function index(): Factory|\Illuminate\Foundation\Application|View|Application
	{
		$roles = Role::query()->latest('created_at')->get();
		return view('permission::roles.list', compact('roles'));
	}

	public function create(): Factory|\Illuminate\Foundation\Application|View|Application
	{
		return view('permission::roles.create');
	}

	public function store(RoleRequest $request): RedirectResponse
	{
		$fields = $request->validated();
		$fields['modified_by'] = Auth::id();
		$role = Role::query()->create($fields);
		return redirect()->route('admin.panel.roles.edit', $role->id)->with('success_msg', tr_helper('contents', 'SUCCESSFULLY_CREATED'));
	}

	public function edit(Role $role): Factory|\Illuminate\Foundation\Application|View|Application
	{
		$rolePermission = $role->permissions()->pluck('route_name', 'permission_id')->toArray();
		$role = $role->toArray();
		$permissions = PermissionController::getArray();
		return view('permission::roles.edit', compact('role', 'permissions', 'rolePermission'));
	}

	public function update(RoleRequest $request, Role $role): RedirectResponse
	{
		$fields = $request->validated();
		if ($fields['full_access']) $fields['is_admin'] = 1;
		$role->update($fields);
		return redirect()->back()->with('success_msg', tr_helper('contents', 'SUCCESSFULLY_UPDATED'));
	}

}
