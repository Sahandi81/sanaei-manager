<?php

namespace Modules\Permission\App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Modules\Permission\App\Http\Requests\PermissionRequest;
use Modules\Permission\App\Models\Permission;
use Modules\Permission\App\Models\Role;
use Modules\Permission\App\Models\RoleHasPermission;

class PermissionController extends Controller
{

	public static function getArray(): Collection|array
	{
		return Permission::query()->orderByDesc('id')->get()->groupBy('parent')->toArray();
	}

	public function syncPermissions(PermissionRequest $request, Role $role): RedirectResponse
	{
		$fields = $request->validated();
		if ($role->full_access){
			return redirect()->back()->with('error_msg', tr_helper('contents', 'CONNOTE_UPDATE_FULL_ACCESS_PERMS'));
		}
		RoleHasPermission::query()->where('role_id', $role->id)->delete();

		$newPermissions = [];
		$creatorID = Auth::id();

		foreach ($fields['permission'] as $key => $item)
		{
			$newPermissions[] = [
				'role_id' 			=> $role->id,
				'permission_id'		=> $key,
				'created_by'		=> $creatorID,
			];
		}

		RoleHasPermission::query()->insert($newPermissions);

		return redirect()->back()->with('success_msg', tr_helper('contents', 'SUCCESSFULLY_UPDATED'));
	}
}
