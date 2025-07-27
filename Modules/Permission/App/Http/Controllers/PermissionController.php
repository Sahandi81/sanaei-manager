<?php

namespace Modules\Permission\App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Modules\Permission\App\Http\Traits\RoleSynchronizer;
use Modules\Permission\App\Models\Permission;

class PermissionController extends Controller
{
	public function index($op = 'client'): Collection|JsonResponse|array
	{
		$permissions = Permission::all()->groupBy('parent');
		if ($op === 'system')
			return $permissions;
		return CustomResponse(true, '', $permissions);
	}

	public function sync(): JsonResponse
	{
		$routesResult = RoleSynchronizer::sync();
		# Just we need filter response to 1 algorithm.
//		return CustomResponse($routesResult['status'], $routesResult['msg'], $routesResult['details']);
		return Response::json(['status' => $routesResult['status'], 'msg' => $routesResult['msg'], 'details' => $routesResult['details']]);
	}
	
}
