<?php

namespace Modules\Permission\App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Auth;

class Role extends Model
{
    use HasFactory;


	protected $fillable = [
		'role_key',
		'title',
		'modified_by',
		'is_admin',
		'full_access',
	];

	protected $hidden = [
		'modified_by'
	];

	public static function getRoles(): Collection
	{
		$userAdminStatus = Auth::user()->role->full_access;
		if ($userAdminStatus) {
			return self::query()->get();
		}else{
			return self::query()->whereNot('full_access', 1)->get();
		}
	}
	public function permissions(): HasManyThrough
	{
		return $this->hasManyThrough(Permission::class, RoleHasPermission::class, 'role_id', 'permissions.id', 'id', 'permission_id');
	}

}
