<?php

namespace Modules\Permission\App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Permission\App\Http\Traits\RoleHelper;
use Modules\Permission\App\Models\Role;

class RoleController extends Controller
{

	public function index(): JsonResponse
	{
		$roles = Role::all(['id', 'title']);
		return CustomResponse(true, '', $roles);
	}

	public function show(Role $role): JsonResponse
	{
		$result = RoleHelper::rolePermissions($role);
		return CustomResponse(true, '', $result);
	}
}
